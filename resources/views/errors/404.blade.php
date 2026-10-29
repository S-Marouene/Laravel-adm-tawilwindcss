@extends('errors.base')

@section('title', __('app.name') . ' — 404')

@section('icon', 'location_off')

@section('heading')
    {{ app()->getLocale() === 'ar' ? 'الصفحة غير موجودة' : 'Page introuvable' }}
@endsection

@section('message')
    {{ app()->getLocale() === 'ar'
        ? 'العنوان المطلوب غير متوفر أو تم نقله. تفضّلوا بزيارة البوابة الرئيسية للمتابعة.'
        : 'L’adresse demandée n’existe pas ou a été déplacée. Reprenez votre démarche depuis le portail principal.' }}
@endsection
