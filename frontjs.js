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


function changeColor(select) {
    if (select.value) {
        select.style.color = 'black'; // Меняем цвет текста на черный при выборе
    } else {
        select.style.color = 'gray'; // Сбрасываем цвет текста на серый
    }
};

$(document).ready(function() {
    $('.delete-btn').click(function(event) {
        event.preventDefault(); // Предотвращаем стандартное поведение формы
        var userId = $(this).siblings('input[name="id"]').val(); // Получаем ID пользователя

        if (confirm('Вы уверены, что хотите удалить этого пользователя?')) {
            $.ajax({
                url: '?table=users&action=delete', // URL вашего обработчика
                type: 'POST',
                data: { id: userId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('tr[data-id="' + userId + '"]').remove(); // Удаляем строку из таблицы
                        alert(response.message);
                    } else {
                        alert(response.message); // Показываем сообщение об ошибке
                    }
                },
                error: function() {
                    alert('Произошла ошибка при удалении пользователя.');
                }
            });
        }
    });
});