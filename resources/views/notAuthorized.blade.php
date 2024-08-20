<!DOCTYPE html>
<html lang="en">
<head>
    @include('layouts.core._head')
    @include('layouts.core._script_vars')
    <style>
        body{padding-top:0!important;}.bsb-btn-xl{--bs-btn-padding-y:0.625rem;--bs-btn-padding-x:1.25rem;--bs-btn-font-size:calc(1.26rem + 0.12vw);--bs-btn-border-radius:var(--bs-border-radius-lg)}@media(min-width:1200px){.bsb-btn-xl{--bs-btn-font-size:1.35rem}}.bsb-btn-2xl{--bs-btn-padding-y:0.75rem;--bs-btn-padding-x:1.5rem;--bs-btn-font-size:calc(1.27rem + 0.24vw);--bs-btn-border-radius:var(--bs-border-radius-lg)}@media(min-width:1200px){.bsb-btn-2xl{--bs-btn-font-size:1.45rem}}.bsb-btn-3xl{--bs-btn-padding-y:0.875rem;--bs-btn-padding-x:1.75rem;--bs-btn-font-size:calc(1.28rem + 0.36vw);--bs-btn-border-radius:var(--bs-border-radius-lg)}@media(min-width:1200px){.bsb-btn-3xl{--bs-btn-font-size:1.55rem}}.bsb-btn-4xl{--bs-btn-padding-y:1rem;--bs-btn-padding-x:2rem;--bs-btn-font-size:calc(1.29rem + 0.48vw);--bs-btn-border-radius:var(--bs-border-radius-lg)}@media(min-width:1200px){.bsb-btn-4xl{--bs-btn-font-size:1.65rem}}.bsb-btn-5xl{--bs-btn-padding-y:1.125rem;--bs-btn-padding-x:2.25rem;--bs-btn-font-size:calc(1.3rem + 0.6vw);--bs-btn-border-radius:var(--bs-border-radius-lg)}@media(min-width:1200px){.bsb-btn-5xl{--bs-btn-font-size:1.75rem}}.bsb-flip{transform:scale(-1)}.bsb-flip-h{transform:scaleX(-1)}.bsb-flip-v{transform:scaleY(-1)}
    </style>
</head>
<body>
    <section class="py-3 py-md-5 min-vh-100 d-flex justify-content-center align-items-center">
        <div class="container">
        <div class="row">
            <div class="col-12">
            <div class="text-center">
                <h2 class="d-flex justify-content-center align-items-center gap-2 mb-4">
                <span class="display-1 fw-bold">401</span>
                </h2>
                <h3 class="h2 mb-2">{{ trans('messages.error.403.title') }}</h3>
                <p class="mb-5 text-danger">{{ $message ?? trans('messages.not_authorized_message') }}</p>
                <a href="javascript:;" onclick="history.back()" class="btn bsb-btn-5xl btn-dark rounded-pill px-5 fs-6 m-0" role="button">
                    {{ trans('messages.go_back') }}
                </a>
            </div>
            </div>
        </div>
        </div>
    </section>
</body>
</html>