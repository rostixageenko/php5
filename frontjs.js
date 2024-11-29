// Функция для скрытия всплывающего сообщения
function hidePopup() {
    var popup = document.getElementById('popup-message');
    if (popup) {
        popup.style.display = 'none';
    }
}

// Если есть сообщение, скрываем его через 5 секунд
window.onload = function() {
    setTimeout(hidePopup, 5000);
};