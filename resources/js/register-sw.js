if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js')
      .then(registration => {
        console.log('ServiceWorker успешно зарегистрирован:', registration);
      })
      .catch(error => {
        console.log('Ошибка регистрации ServiceWorker:', error);
      });
  });
}
