window.tr = window.tr || function (key) {
    return (window.i18n && window.i18n[key]) || key;
};

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

async function fetchWithRetry(url, options = {}, attempts = 3) {
    let lastError;

    for (let i = 1; i <= attempts; i++) {
        try {
            const response = await fetch(url, options);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response;
        } catch (error) {
            lastError = error;
            if (i < attempts) {
                await sleep(500 * i);
            }
        }
    }

    throw lastError;
}

async function vote(postId, type, btnElement) {
    try {
        const response = await fetch('api/action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ act: 'vote', id: postId, type: type })
        });
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            // Ищем элемент счета рядом с нажатой кнопкой и обновляем его
            const container = btnElement.closest('.vote-column');
            const scoreDiv = container.querySelector('.vote-score');
            scoreDiv.innerText = data.newScore;

            // Визуально подсвечиваем (опционально)
            container.querySelectorAll('.vote-btn').forEach(b => b.style.color = '');
            if (type === 'up') btnElement.style.color = '#ff4500';
            else btnElement.style.color = '#7193ff';
        } else {
            alert(tr('error') + ": " + (data.error || tr('unknown_error')));
        }
    } catch (e) {
        console.error(e);
    }
}

// Функция отправки комментария
async function sendComment(event, postId) {
    event.preventDefault(); // Останавливаем стандартную отправку формы

    const form = event.target;
    const input = form.querySelector('textarea');
    const text = input.value;

    if (!text.trim()) return;

    try {
        const response = await fetch('api/action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ act: 'comment', article_id: postId, text: text })
        });
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            input.value = ''; // Очистить поле

            // Добавляем новый комментарий в список (безопасно, через textContent)
            const commentsList = document.querySelector('.comments-container');

            const commentDiv = document.createElement('div');
            commentDiv.style.cssText = 'border-bottom:1px solid var(--border); padding:10px 0; transition: 1s;';

            const authorEl = document.createElement('b');
            authorEl.textContent = data.author + ':';

            const dateEl = document.createElement('div');
            dateEl.style.cssText = 'font-size:0.8em; color:gray';
            dateEl.textContent = data.date;

            commentDiv.appendChild(authorEl);
            commentDiv.appendChild(document.createTextNode(' ' + data.text));
            commentDiv.appendChild(dateEl);

            // Вставляем в начало
            commentsList.prepend(commentDiv);
        } else {
            alert(tr('error') + ": " + data.error);
        }
    } catch (e) {
        console.error(e);
    }
}


async function toggleFavorite(postId, btnElement) {
    try {
        const response = await fetch('api/action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ act: 'fav', id: postId })
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            if (data.favorite) {
                btnElement.innerText = tr('fav_in');
                btnElement.style.color = "gold";
                btnElement.style.fontWeight = "bold";
            } else {
                btnElement.innerText = tr('fav_add');
                btnElement.style.color = "";
                btnElement.style.fontWeight = "";
            }
        } else {
            alert(tr('error') + ": " + (data.error || tr('unknown_error')));
        }
    } catch (e) {
        console.error(e);
    }
}
var path = window.location.pathname;
var page = path.split("/").pop();

if (page == "index.php" || page == "") {
    let isLoading = false;
    let skip = 10; // skip first 10
    let reachedEnd = false; // is posts end


    window.addEventListener('scroll', () => {
        if (isLoading || reachedEnd) return;
        if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 200) {
            loadMorePosts(10);
        }
    });


    async function loadMorePosts(posts = 5) {
        isLoading = true;
        const loader = document.getElementById('loader');
        if (loader) loader.style.display = 'block';

        try {
            // currentSort определен в index.php
            const sort = (typeof currentSort !== 'undefined') ? currentSort : 'new';

            // Запрашиваем PHP файл
            const response = await fetchWithRetry(`api/load_posts.php?skip=${skip}&sort=${sort}&limit=${posts}`);
            const html = await response.text();

            if (html.trim() === "") {
                reachedEnd = true;
                if (loader) loader.innerText = tr('no_more_posts');
            } else {
                // Добавляем полученный HTML в конец списка
                document.getElementById('posts-wrapper').insertAdjacentHTML('beforeend', html);
                skip += posts; // Увеличиваем отступ для следующего раза
                if (loader) loader.style.display = 'none';
            }
        } catch (e) {
            console.error("Ошибка загрузки:", e);
            if (loader) {
                loader.innerText = tr('load_failed');
                loader.style.display = 'block';
            }
        } finally {
            isLoading = false;
        }
    }
}
