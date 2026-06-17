{{--@extends('errors::minimal')--}}

{{--@section('title', __('Service Unavailable'))--}}
{{--@section('code', '503')--}}
{{--@section('message', __('Service Unavailable'))--}}
        <!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Технические работы</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white flex items-center justify-center h-screen">
<div class="text-center p-6 max-w-lg">
    <h1 class="text-4xl font-bold text-yellow-500 mb-4">Технические работы</h1>
    <p class="text-gray-300 text-lg mb-6">
        Я обновляю систему квестов и улучшаю всякую хуйню.
        Пожалуйста, зайдите через минут 15.
    </p>
    <div class="animate-pulse text-sm text-gray-500">
        Семён уже работает над этим...
    </div>
</div>
</body>
</html>