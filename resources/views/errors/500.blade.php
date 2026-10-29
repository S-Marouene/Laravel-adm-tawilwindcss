@extends('errors.base')

@section('title', __('app.name') . ' — 500')

@section('icon', 'report')

@section('heading')
    {{ app()->getLocale() === 'ar' ? 'خلل تقني في الخدمة' : 'Incident technique' }}
@endsection

@section('message')
    {{ app()->getLocale() === 'ar'
        ? 'حدث خلل غير متوقع في الخدمة. يعمل فريقنا على استعادة الوصول في أقرب وقت.'
        : 'Une erreur inattendue est survenue. Nos équipes rétablissent le service dans les meilleurs délais.' }}
@endsection
