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

function previewImage(input) {
    const uploadPhotoDiv = input.parentElement;
    const imgPreview = uploadPhotoDiv.querySelector('img');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            imgPreview.src = e.target.result; // Устанавливаем изображение
            imgPreview.style.display = 'block'; // Показываем изображение
        }
        reader.readAsDataURL(input.files[0]); // Читаем файл как Data URL
    }
};


