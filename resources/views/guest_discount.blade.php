<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Твоя персональная скидка | Game Over</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <style>
        /* Анимация пульсации для скидки */
        @keyframes pulse-glow {
            0%, 100% { filter: drop-shadow(0 0 15px rgba(220, 38, 38, 0.4)); }
            50% { filter: drop-shadow(0 0 25px rgba(220, 38, 38, 0.8)); }
        }
        .glow-text { animation: pulse-glow 3s infinite ease-in-out; }
        .logo-font { font-family: 'Playfair Display', serif; }

        /* Эффект мерцания сломанного неона */
        @keyframes text-flicker {
            0%, 19.999%, 22%, 62.999%, 64%, 64.999%, 70%, 100% { opacity: 1; text-shadow: 0 0 8px rgba(220,38,38,0.6); }
            20%, 21.999%, 63%, 63.999%, 65%, 69.999% { opacity: 0.4; text-shadow: none; }
        }
        /* Эффект глитча */
        @keyframes glitch-skew {
            0%, 9%, 11%, 100% { transform: skew(0deg) translateX(0); }
            10% { transform: skew(-8deg) translateX(-2px); }
            10.5% { transform: skew(5deg) translateX(2px); }
        }
        .flicker { animation: text-flicker 4s infinite alternate; }
        .glitch { animation: glitch-skew 5s infinite; display: inline-block; }

        /* Фоновый паттерн (квестовая сетка) */
        .bg-pattern {
            background-image:
                    linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 40px 40px;
            animation: pan-background 30s linear infinite;
        }
        @keyframes pan-background {
            0% { background-position: 0 0; }
            100% { background-position: 40px 40px; }
        }

        /* Классы для анимации появления при скролле */
        .reveal {
            opacity: 0;
            transform: translateY(50px);
            transition: all 0.8s cubic-bezier(0.5, 0, 0, 1);
        }
        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        /* Индикатор скролла */
        @keyframes bounce-subtle {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(10px); }
        }
        .scroll-indicator { animation: bounce-subtle 2s infinite ease-in-out; }

        /* Скрываем скроллбар для эстетики */
        ::-webkit-scrollbar { width: 0px; background: transparent; }
    </style>
</head>
<body class="bg-zinc-950 text-zinc-100 antialiased selection:bg-red-600 selection:text-white overflow-hidden">

<div class="fixed inset-0 z-0 bg-pattern pointer-events-none"></div>
<div class="fixed top-0 left-1/2 -translate-x-1/2 w-[800px] h-[300px] bg-red-900/10 blur-[100px] rounded-full pointer-events-none z-0"></div>
<div class="fixed bottom-0 left-1/2 -translate-x-1/2 w-[600px] h-[200px] bg-red-800/10 blur-[80px] rounded-full pointer-events-none z-0"></div>

<main class="h-[100dvh] w-full overflow-y-auto overflow-x-hidden snap-y snap-mandatory relative z-10 scroll-smooth">

    <section class="h-[100dvh] w-full snap-start flex flex-col p-4 relative">
        <div class="flex-1"></div>

        <div class="flex flex-col items-center justify-center">
            <div class="mb-4 logo-font text-center uppercase z-10 glitch reveal">
                <div class="text-4xl sm:text-5xl tracking-widest mb-2 font-black">
                    <span class="text-red-600 flicker inline-block">Game</span> <span class="text-zinc-100">Over</span>
                </div>
                <div class="text-sm sm:text-base tracking-[0.2em] font-bold">
                    <span class="text-zinc-100">Quest</span> <span class="text-red-600 flicker inline-block">Club</span>
                </div>
            </div>
            <p class="text-zinc-400 text-xs sm:text-sm text-center uppercase tracking-widest leading-relaxed font-medium reveal" style="transition-delay: 0.1s;">
                Более 10000 игр <span class="mx-1.5 text-red-600/50">•</span> 5000 мероприятий
            </p>

            <div class="mt-8 flex flex-col items-center justify-center p-4 bg-zinc-900/40 backdrop-blur-md border border-zinc-800/50 rounded-2xl reveal w-48 shadow-[0_0_20px_rgba(0,0,0,0.3)]" style="transition-delay: 0.3s;">
                <a href="https://instagram.com/gameover_pvl" target="_blank" class="flex flex-col items-center group cursor-pointer">
                    <svg class="w-6 h-6 text-zinc-400 mb-2 group-hover:text-red-500 transition-colors" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.88z"/></svg>
                    <div class="text-3xl font-black text-white tracking-wider ig-counter" data-target="17300">0</div>
                    <div class="text-[9px] uppercase tracking-[0.2em] text-zinc-500 mt-1 font-semibold group-hover:text-zinc-300 transition-colors">Подписчиков</div>
                </a>
            </div>
        </div>

        <div class="flex-1 flex flex-col justify-end items-center pb-8">
            <div class="text-zinc-500 scroll-indicator reveal flex flex-col items-center" style="transition-delay: 0.6s;">
                <p class="text-[10px] uppercase tracking-[0.3em] mb-2 text-center opacity-70">Листай</p>
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                </svg>
            </div>
        </div>
    </section>

    <section class="min-h-[100dvh] w-full snap-start flex flex-col items-center py-12 px-4 gap-8">

        <div class="w-full max-w-sm reveal">
            <div class="bg-zinc-900/80 backdrop-blur-xl border border-zinc-800 rounded-3xl p-8 text-center shadow-[0_0_40px_rgba(0,0,0,0.5)] relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-zinc-900 via-red-600 to-zinc-900"></div>
                <p class="text-zinc-500 text-xs font-semibold tracking-widest uppercase mb-2">Доступ разрешен</p>
                <h1 class="text-xl font-bold text-white mb-6">Привет, <span class="text-red-500">{{ $name }}</span>!</h1>
                <div class="py-2">
                    <p class="text-zinc-500 text-xs mb-1">Твоя постоянная скидка</p>
                    <div class="text-7xl font-black bg-gradient-to-br from-red-400 to-red-700 bg-clip-text text-transparent glow-text">
                        {{ $discount }}%
                    </div>
                </div>
                <p class="text-zinc-500 text-xs mt-6 px-4">Покажи этот экран администратору перед началом игры</p>
            </div>
        </div>

        <div class="w-full max-w-sm space-y-3 pb-12 reveal" style="transition-delay: 0.2s;">
            <h3 class="text-center text-zinc-500 text-xs uppercase tracking-wider mb-4">Наши контакты</h3>

            <a href="https://instagram.com/gameover_pvl" target="_blank" class="flex items-center justify-between w-full p-4 bg-zinc-900/50 backdrop-blur-md border border-zinc-800 rounded-xl hover:bg-zinc-800 hover:border-red-500/50 transition-all group">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-zinc-400 group-hover:text-white transition-colors" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.88z"/></svg>
                    <span class="text-zinc-300 text-sm font-medium group-hover:text-white transition-colors">Instagram</span>
                </div>
                <svg class="w-4 h-4 text-zinc-600 group-hover:text-red-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>

            <a href="https://wa.me/77473335202" target="_blank" class="flex items-center justify-between w-full p-4 bg-zinc-900/50 backdrop-blur-md border border-zinc-800 rounded-xl hover:bg-zinc-800 hover:border-red-500/50 transition-all group">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-zinc-400 group-hover:text-white transition-colors" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    <span class="text-zinc-300 text-sm font-medium group-hover:text-white transition-colors">WhatsApp</span>
                </div>
                <svg class="w-4 h-4 text-zinc-600 group-hover:text-red-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>

            <a href="https://go.2gis.com/I9LwJ" target="_blank" class="flex items-center justify-between w-full p-4 bg-zinc-900/50 backdrop-blur-md border border-zinc-800 rounded-xl hover:bg-zinc-800 hover:border-red-500/50 transition-all group">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-zinc-400 group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.243-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    <div class="text-left">
                        <span class="block text-zinc-300 text-sm font-medium group-hover:text-white transition-colors">Бектурова 21</span>
                        <span class="block text-zinc-500 text-[10px] mt-0.5 uppercase tracking-wide">Эффект Лазаря</span>
                    </div>
                </div>
                <svg class="w-4 h-4 text-zinc-600 group-hover:text-red-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>

            <a href="https://go.2gis.com/j3fQN" target="_blank" class="flex items-center justify-between w-full p-4 bg-zinc-900/50 backdrop-blur-md border border-zinc-800 rounded-xl hover:bg-zinc-800 hover:border-red-500/50 transition-all group">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-zinc-400 group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.243-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    <div class="text-left">
                        <span class="block text-zinc-300 text-sm font-medium group-hover:text-white transition-colors">Кудайбердиева 2/1</span>
                        <span class="block text-zinc-500 text-[10px] mt-0.5 uppercase tracking-wide">Синистер</span>
                    </div>
                </div>
                <svg class="w-4 h-4 text-zinc-600 group-hover:text-red-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </section>

</main>

<script>
    // Функция анимации бегущих цифр для счетчика
    function animateCounter(el) {
        const target = +el.getAttribute('data-target');
        const duration = 2000; // 2 секунды на всю анимацию
        const stepTime = Math.abs(Math.floor(duration / target));
        let current = 0;

        // Если вы захотите получать данные с вашего сервера, раскомментируйте это:
        /*
        fetch('https://ваш-домен.com/api/get-ig-followers')
            .then(res => res.json())
            .then(data => { target = data.followers; })
            .catch(err => console.error(err));
        */

        const timer = setInterval(() => {
            current += Math.ceil(target / 100); // Прибавляем кусками для скорости
            if (current >= target) {
                el.innerText = target.toLocaleString('ru-RU');
                clearInterval(timer);
            } else {
                el.innerText = current.toLocaleString('ru-RU');
            }
        }, 20);
    }

    document.addEventListener('DOMContentLoaded', () => {
        const observerOptions = {
            root: document.querySelector('main'),
            rootMargin: '0px',
            threshold: 0.15 // Срабатывает чуть раньше
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                }
            });
        }, observerOptions);

        const revealElements = document.querySelectorAll('.reveal');
        revealElements.forEach(el => observer.observe(el));

        // Запуск анимации для первого экрана и запуск счетчика
        setTimeout(() => {
            const firstReveal = document.querySelectorAll('section:nth-child(1) .reveal');
            firstReveal.forEach(el => el.classList.add('active'));

            const counterElement = document.querySelector('.ig-counter');
            if(counterElement) {
                setTimeout(() => animateCounter(counterElement), 400); // Ждем пока блок появится
            }
        }, 100);
    });
</script>
</body>
</html>
