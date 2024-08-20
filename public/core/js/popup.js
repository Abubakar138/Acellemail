class Popup {
    constructor(options, callback) {
        if (typeof(callback) != 'undefined') {
            options.callback = callback;
        }
        this.init(options);
    }

    init(options) {
        var _this = this;
        this.id = '_' + Math.random().toString(36).substr(2, 9);
        this.options = {};
        this.popup = $('.popup[id='+this.id+']');
        this.backs = [];
        this.initHtml = `
            <div class="modal" id="`+this.id+`" tabindex="-1">
                <div class="modal-dialog shadow modal-default">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title placeholder-glow">
                                <span class="placeholder col-6"></span>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body ">
                            <p class="card-text placeholder-glow">
                                <span class="placeholder col-7"></span>
                                <span class="placeholder col-4"></span>
                                <span class="placeholder col-4"></span>
                                <span class="placeholder col-6"></span>
                                <span class="placeholder col-8"></span>
                            </p>
                            <div class="mt-4 text-center placeholder-glow">
                                <a class="btn btn-default disabled placeholder col-2" aria-disabled="true"></a>
                                <a class="btn btn-default disabled placeholder col-2" aria-disabled="true"></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // options
        if (typeof(options) !== 'undefined') {
            this.options = options;
        }
        
        if (!this.popup.length) {
            var popup = $(this.initHtml);
            $('body').append(popup);
            
            this.popup = popup;
        }
        
        this.modal = new bootstrap.Modal(document.getElementById(this.id), {
            backdrop: this.options.backdrop ?? 'static'
        });

        this.popup[0].addEventListener('hidden.bs.modal', function (event) {
            _this.hide();

            // onclose
            if (_this.options.onclose != null) {
                _this.options.onclose();
            }
        })

        this.initMask();
    }

    reset() {
        this.modal.dispose();
        this.popup.remove();
        this.init(this.options);
    }
    
    show() {
        this.modal.show();

        fixPopupLayers();
    }
    
    hide() {
        this.modal.hide();

        // onclose
        if (this.options.onclose != null) {
            this.options.onclose();
        }

        if (typeof(this.onHide) !== 'undefined') {
            this.onHide();
        }

        this.reset();
    }
    
    static hide() {
        $('.popup').fadeOut();
        $('html').css('overflow', 'auto');
    }

    goBack() {
        var _this = this;

        if (_this.backs.length > 0) {
            var backing = _this.backs.pop();
            backing = $.extend({}, backing, {noHistory: true});

            _this.load(backing);
        } else {
            _this.hide();
        }
    }

    toogleBackButton() {
        if (this.backs.length) {
            this.popup.find('.back').show();
        } else {
            this.popup.find('.back').hide();
        }
    }
    
    applyJs() {
        var _this = this;
        
        // init js
        initJs(_this.popup);
        
        // back button
        _this.popup.find('.back').on('click', function() {
            _this.goBack();
        });
        
        // click close button
        _this.popup.find(".close, [data-dismiss=modal]").click(function(){
            _this.hide();
        });
    }

    initMask() {
        var tags = 'h1,h2,h3,h4,h5,p,.btn,span,input,select,.alert,.progress-bar,label,a,textarea,i';
        this.popup.find(tags).each(function() {
            if (!$(this).parent(tags).length) {
                $(this).addClass('popup-animated-background');
            }
        });
    }

    mask() {
        this.popup.addClass('popup-loading');
    }

    loading() {
        this.mask();
    }

    unmask() {
        this.popup.removeClass('popup-loading');
        this.popup.find('.popup-progress').remove();

        this.popup.find('*').removeClass('popup-animated-background');
    }
    
    load(options, callback) {
        var _this = this;
        var newOptions = {};

        if (typeof(options) == 'string') {
            newOptions.url = options;
        } else {
            // update options            
            if (typeof(options) !== 'undefined') {
                newOptions = options;
            }
        }

        //
        if (typeof(callback) != 'undefined') {
            newOptions.callback = callback;
        }
        
        // save current options
        if (typeof(newOptions.noHistory) == 'undefined' &&
            typeof(newOptions.url) !== 'undefined' &&
            typeof(_this.options) !== 'undefined' &&
            typeof(_this.options.url) !== 'undefined' &&
            newOptions.url != _this.options.url
        ) {
            this.backs.push(_this.options);
        }

        // update options            
        _this.options = $.extend({}, _this.options, newOptions);
        
        // show popup
        _this.show();

        // effect
        _this.mask();

        // load from url
        $.ajax({
            url: _this.options.url,
            method: 'GET',
            dataType: 'html',
            data: _this.options.data,
            globalError: !_this.options.fail,
        }).done(function(response) {
            _this.popup.html(response);
            
            // apply js for new content
            _this.applyJs();

            // hide/show backe button
            _this.toogleBackButton();

            // 
            _this.unmask();

            // loaded callback
            if (typeof(_this.options.callback) != 'undefined') {
                _this.options.callback();
            }
        }).fail(function(jqXHR, textStatus, errorThrown){
            // fail callback
            if (_this.options.fail) {
                _this.options.fail(jqXHR);
            } else {
                console.log(errorThrown);
            }
        }).always(function() {
            // effect
            _this.unmask();
        });
    }
    
    loadHtml(html, callback) {
        var _this = this;

        //
        if (typeof(callback) != 'undefined') {
            _this.options.callback = callback;
        }

        // show popup
        _this.show();
        
        _this.popup.html(html);
        
        // apply js for new content
        _this.applyJs();

        // hide/show backe button
        _this.toogleBackButton();

        //
        _this.unmask();

        // loaded callback
        if (typeof(_this.options.callback) != 'undefined') {
            _this.options.callback();
        }
    }
}