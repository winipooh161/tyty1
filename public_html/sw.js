// Имя кеша
const CACHE_NAME = 'sticap-cache-v1';

// Файлы для кеширования
const urlsToCache = [
  '/',
  '/offline.html',
  '/css/app.css',
  '/js/app.js',
  '/manifest.json',
  // Добавьте дополнительные ресурсы по необходимости
];

// Установка Service Worker
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Opened cache');
        return cache.addAll(urlsToCache);
      })
  );
});

// Перехват запросов
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Возврат из кеша, если найдено
        if (response) {
          return response;
        }
        
        // Иначе - запрос к сети
        return fetch(event.request.clone())
          .then(response => {
            // Проверка, что запрос успешен
            if (!response || response.status !== 200 || response.type !== 'basic') {
              return response;
            }
            
            // Клонируем ответ, т.к. он может быть использован только один раз
            const responseToCache = response.clone();
            
            caches.open(CACHE_NAME)
              .then(cache => {
                cache.put(event.request, responseToCache);
              });
              
            return response;
          })
          .catch(() => {
            // Показываем офлайн страницу при ошибке
            if (event.request.mode === 'navigate') {
              return caches.match('/offline.html');
            }
            return new Response('Ресурс недоступен в офлайн-режиме', {
              status: 503,
              statusText: 'Service Unavailable'
            });
          });
      })
  );
});

// Обновление Service Worker
self.addEventListener('activate', event => {
  const cacheWhitelist = [CACHE_NAME];
  
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            // Удаление старых кешей
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});
