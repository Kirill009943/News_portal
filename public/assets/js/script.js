window.addEventListener('DOMContentLoaded', () => {
    //applySettings(); // Применить тему и шрифт
});

function applySettings() {
    const theme = localStorage.getItem('theme') || 'light';
    const textSize = localStorage.getItem('textSize') || '14px';
    
    if (theme === 'dark') document.body.classList.add('dark-theme');
    else document.body.classList.remove('dark-theme');
    
    document.body.style.fontSize = textSize;
}

function sharePost(id) {
    const url = window.location.origin + `/post.php?id=${id}`;
    navigator.clipboard.writeText(url).then(() => alert("Ссылка на пост скопирована!"));
}