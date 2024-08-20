<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js" integrity="sha512-D9gUyxqja7hBtkWpPWGt9wfbfaMGVt9gnyCvYa+jojwwPHLCzUm5i8rpk7vD7wNee9bA35eYIjobYPaQuKS1MQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
/*! `curl` grammar compiled for Highlight.js 11.3.1 */
(()=>{var e=(()=>{"use strict";return e=>{const n={className:"string",begin:/"/,
end:/"/,contains:[e.BACKSLASH_ESCAPE,{className:"variable",begin:/\$\(/,
end:/\)/,contains:[e.BACKSLASH_ESCAPE]}],relevance:0},a={className:"number",
variants:[{begin:e.C_NUMBER_RE}],relevance:0};return{name:"curl",
aliases:["curl"],keywords:"curl",case_insensitive:!0,contains:[{
className:"literal",begin:/(--request|-X)\s/,contains:[{className:"symbol",
begin:/(get|post|delete|options|head|put|patch|trace|connect)/,end:/\s/,
returnEnd:!0}],returnEnd:!0,relevance:10},{className:"literal",begin:/--/,
end:/[\s"]/,returnEnd:!0,relevance:0},{className:"literal",begin:/-\w/,
end:/[\s"]/,returnEnd:!0,relevance:0},n,{className:"string",begin:/\\"/,
relevance:0},{className:"string",begin:/'/,end:/'/,relevance:0
},e.APOS_STRING_MODE,e.QUOTE_STRING_MODE,a,{match:/(\/[a-z._-]+)+/}]}}})()
;hljs.registerLanguage("curl",e)})();

</script>
  
<div class="page-title">
    <ul class="breadcrumb breadcrumb-caret position-right">
        <li class="breadcrumb-item"><a href="{{ action("HomeController@index") }}">{{ trans('messages.home') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ action("MailListController@index") }}">{{ trans('messages.lists') }}</a></li>
    </ul>

    <div class="d-flex align-items-center my-4 pt-2">
        <h1 class="mb-0 me-auto">
            <span class="text-semibold">{{ $list->name }}</span>
        </h1>
        @if (config('app.demo'))
            <style>
                .avatars-stack {
            
                }
                .avatars-stack img {
                    width: 32px;
                    height: 32px;
                    border-radius: 100%;
                    display: inline-block;
                    margin-left: -13px;
                    box-shadow: 0 0 2px rgba(0,0,0,0.1);
                    border: solid 0px #ccc;
                }
                .avatars-stack .btn-trans {
                    background-color: transparent!important;
                    border-radius: 100px;
                    border: none;
                }
                .avatars-stack .btn-trans:hover {
                    background-color: rgba(0, 0, 0, 0.04)!important;
                    border-radius: 100px;
                    box-shadow: none!important;
                }
            </style>
            <a class="avatars-stack d-flex align-items-center" href="{{ action('SubscriberController@index', $list->uid) }}">
                <div class=" me-2">
                    <img src="https://i.pravatar.cc/300?v={{ rand(0,10000000) }}" alt="">
                    <img src="https://i.pravatar.cc/300?v={{ rand(0,10000000) }}" alt="">
                    <img src="https://i.pravatar.cc/300?v={{ rand(0,10000000) }}" alt="">
                </div>
                <div class="me-4">
                    <button class="btn btn-default btn-trans"> Last subscription 3 days before</button>
                </div>
            </a>
        @endif
        <div>
            <a data-control="list-webhook" href="{{ action('MailListController@webhookJson', [
                'uid' => $list->uid,
            ]) }}" class="btn btn-secondary me-1">
                {{ trans('messages.list.webhook') }}
            </a>
            <div class="btn-group">
                <button role="button" class="btn btn-light" data-bs-toggle="dropdown">
                    {{ trans('messages.change_list') }} <span class="material-symbols-rounded ms-2">double_arrow</span>
                </button>
                <ul class="dropdown-menu">
                    @forelse ($list->otherLists() as $l)
                        <li>
                            <a class="dropdown-item" href="{{ action('MailListController@overview', ['uid' => $l->uid]) }}">
                                {{ $l->readCache('LongName', $l->name) }}
                            </a>
                        </li>
                    @empty
                        <li style="pointer-events:none;"><a href="#" class="dropdown-item">({{ trans('messages.empty') }})</a></li>
                    @endforelse
                </ul>
            </div>
        </div>

    </div>

    <span class="badge badge-info bg-info-800 badge-big">{{ number_with_delimiter($list->readCache('SubscriberCount')) }}</span> {{ trans('messages.subscribers') }}
</div>

<script>
    var listWebhook;

    $(function() {
        listWebhook = new ListWebhook({
            button: $('[data-control="list-webhook"]'),
        });
    });

    var ListWebhook = class {
        constructor(options) {
            this.button = options.button;
            this.url = this.button.attr('href');
            this.popup = new Popup({
                url: this.url,
            });

            // events
            this.events();
        }

        events() {
            this.button.on('click', (e) => {
                e.preventDefault();
                
                this.load();
            })
        }

        load() {
            this.popup.load();
        }
    }
</script>
