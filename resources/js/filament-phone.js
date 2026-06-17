import { AsYouType } from 'libphonenumber-js';
window.AsYouType = AsYouType;
document.addEventListener('alpine:init', () => {
    Alpine.data('phoneMask', (initialState = '') => ({
        init() {
            // Если при загрузке есть значение, форматируем его сразу
            if (this.$el.value) {
                this.format();
            }
        },
        format() {
            // Создаем экземпляр для России (или вашей дефолтной страны)
            const asYouType = new AsYouType('KZ');
            const formatted = asYouType.input(this.$el.value);

            // Обновляем значение в поле ввода
            this.$el.value = formatted;
        }
    }));
});