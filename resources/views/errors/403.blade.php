@extends('errors.base')

@section('title', __('app.name') . ' — 403')

@section('icon', 'lock')

@section('heading')
    {{ app()->getLocale() === 'ar' ? 'وصول غير مصرّح به' : 'Accès non autorisé' }}
@endsection

@section('message')
    {{ app()->getLocale() === 'ar'
        ? 'لا تملكون صلاحية الاطلاع على هذا المحتوى. اتصلوا بمؤسستكم المعنية إن كنتم ترون أن ذلك خطأ في التصنيف.'
        : 'Vous n’avez pas les droits nécessaires pour consulter cette ressource. Contactez l’administration si vous pensez qu’il s’agit d’une erreur.' }}
@endsection
