if(!window.awz_bp_modal){
    window.awz_bp_modal = {
        last_items: [],
        lastSign: '',
        objectManager: null,
        initedHandlers: false,
        map: false,
        loader_template: function(){
            var loader_mess = window.BX ? window.BX.message('AWZ_BELPOST_JS_SERV_LOADER') : 'Loading...';
            return '<div class="awz-bp-preload"><div class="awz-bp-load">'+loader_mess+'</div></div>';
        },
        template: function(title){
            
            var close_msg = window.BX ? window.BX.message('AWZ_BELPOST_JS_CLOSE') : 'close';
            var ht = '<div class="awz-bp-modal-content-bg"></div>' +
                '<a class="awz-bp-close" href="#"><div>\n' +
                '        <div class="awz-bp-close-leftright"></div>\n' +
                '        <div class="awz-bp-close-rightleft"></div>\n' +
                '        <span class="awz-bp-close-close-btn">'+close_msg+'</span>\n' +
                '    </div></a>' +
                '<div class="awz-bp-modal-content"><div class="awz-bp-modal-content-wrap">'+
                '<div class="awz-bp-modal-header">'+
                ''+title+
                '</div>'+
                '<div class="awz-bp-modal-body"><div class="awz-bp-contentWrap"></div>' +
                '</div>'+
                '</div></div>';

            return ht;
        },
        hideLoader: function(){
            $('.awz-bp-preload').remove();
        },
        setError: function (mess){
            $('.awz-bp-contentWrap').html('<div class="awz-bp-modal-error">'+mess+'</div>');
            this.hideLoader();
        },
        hide: function(){
            $('.awz-bp-modal-content').remove();
            $('.awz-bp-modal-content-bg').remove();
            $('.awz-bp-close').remove();
        },
        show: function(title, params){
            this.lastSign = params;
            $('body').append(this.template(title));
            var h = $(window).height();
            var w = $(window).width();
            if(w > 860) {
                w = Math.ceil(w*0.8);
                h = Math.ceil(h*0.8);
                $('.awz-bp-modal-content-wrap').css({
                    'margin-top':Math.ceil(($(window).height()-h)/2)+'px',
                    'width':w+'px',
                    'height': h+'px'
                });
            }else{
                $('.awz-bp-close').addClass('awz-bp-close-mobile');
                w = Math.ceil(w);
                h = Math.ceil(h);
                $('.awz-bp-modal-content-wrap').css({'width':w+'px', 'height': h+'px'});
            }
            $('.awz-bp-modal-body .awz-bp-contentWrap').append('<div class="awz-bp-map" id="awz-bp-map"></div>');
            var hmap = $('.awz-bp-modal-content-wrap').height() - $('.awz-bp-modal-header').height() - 30;
            $('.awz-bp-modal-body').css({'height':hmap+'px'});

            this.getPickpointsList(params);
        },
        loadBaloonAjax: function(e, params, el, pvz, callback){

            var serv_error = window.BX ? window.BX.message('AWZ_BELPOST_JS_SERV_ERR') : 'server error';
            var loader_mess = window.BX ? window.BX.message('AWZ_BELPOST_JS_SERV_LOADER') : 'Loading...';

            if(e){
                var objectId = e.get('objectId'),
                    obj = window.awz_bp_modal.objectManager.objects.getById(objectId);
                obj.properties.balloonContent = loader_mess;
                window.awz_bp_modal.objectManager.objects.balloon.open(objectId);
                var id = obj.properties.id;
            }else{
                var id = pvz;
                el.html(loader_mess);
            }

            $.ajax({
                url: '/bitrix/services/main/ajax.php?action=awz:belpost.api.pickpoints.baloon',
                method: 'POST',
                data: {
                    signed: params,
                    id: id
                },
                success: function(resp){
                    var data = resp.data;
                    //console.log(data);
                    if(resp.status === 'error'){
                        var msg = '';
                        var k;
                        for(k in resp.errors){
                            var err = resp.errors[k];
                            msg += err.message+'<br><br>';
                        }
                        if(e) {
                            obj.properties.balloonContent = msg;
                            window.awz_bp_modal.objectManager.objects.balloon.setData(obj);
                        }else{
                            el.html(msg);
                        }
                    }else if(resp.status === 'success'){
                        if(e) {
                            obj.properties.balloonContent = data;
                            window.awz_bp_modal.objectManager.objects.balloon.setData(obj);
                            //debugger;
                        }else{
                            el.html(data);
                        }
                    }
                    if(typeof callback === 'function'){
                        callback.call();
                    }
                },
                error: function(){
                    if(e) {
                        obj.properties.balloonContent = serv_error;
                        window.awz_bp_modal.objectManager.objects.balloon.setData(obj);
                    }else{
                        el.html(serv_error);
                    }
                    if(typeof callback === 'function'){
                        callback.call();
                    }
                }
            });
        },
        initMap: function(){

            var controls = ['zoomControl', 'searchControl'];
            if(window.hasOwnProperty('_awz_bp_lib_setSearchAddress') && window._awz_bp_lib_setSearchAddress != 'Y'){
                controls = ['zoomControl'];
            }

            this.map = new ymaps.Map("awz-bp-map",{
                center: [53.873516, 27.416178],
                zoom: 14,
                controls: controls
            },{
                balloonMaxWidth: 280
            });

        },
        checkFilter: function(){
            var payments = [];
            $('.awz-bp-modal-filter-payment').each(function(){
                if($(this).hasClass('active')) payments.push($(this).attr('data-payment'));
            });

            var type = [];
            $('.awz-bp-modal-filter-type').each(function(){
                if($(this).hasClass('active')) type.push($(this).attr('data-type'));
            });

            var objectsArray = window.awz_bp_modal.getPoints(payments, type);
            window.awz_bp_modal.objectManager.add(objectsArray);
        },
        initHandlers: function(){
            if(this.initedHandlers) return;
            this.initedHandlers = true;
            $(document).on('click','.awz-bp-modal-filter-payment',function(e){
                if(!!e) e.preventDefault();
                window.awz_bp_modal.objectManager.removeAll();
                if($(this).hasClass('active')){
                    $(this).removeClass('active');
                }else{
                    $(this).addClass('active');
                }
                window.awz_bp_modal.checkFilter();
            });
            $(document).on('click','.awz-bp-modal-filter-type',function(e){
                if(!!e) e.preventDefault();
                window.awz_bp_modal.objectManager.removeAll();
                if($(this).hasClass('active')){
                    $(this).removeClass('active');
                }else{
                    $(this).addClass('active');
                }
                window.awz_bp_modal.checkFilter();
            });
            $(document).on('click','.awz-bp-modal-content-bg',function(e){
                if(!!e) e.preventDefault();
                window.awz_bp_modal.hide();
            });
            $(document).on('click','.awz-bp-close',function(e){
                if(!!e) e.preventDefault();
                window.awz_bp_modal.hide();
            });
            $(document).on('click','.awz-bp-select-pvzadmin',function(e){
                if(!!e) e.preventDefault();
                $('#AWZ_BPPOINT_ID').val($(this).attr('data-id'));
            });
            $(document).on('click','.awz-bp-select-pvz',function(e){
                if(!!e) e.preventDefault();
                var form = $('#AWZ_BPPOINT_LINK').parents('form');
                if(!$('#AWZ_BPPOINT_ID').length){
                    //console.log(form);
                    form.prepend('<input type="hidden" name="AWZ_BPPOINT_ID" id="AWZ_BPPOINT_ID" value="">');
                }
                $('#AWZ_BPPOINT_ID').val($(this).attr('data-id'));
                if($('#AWZ_BPPOINT_INFO').length){
                    try{
                        window.BX.Sale.OrderAjaxComponent.sendRequest();
                    }catch (e) {
                        window.awz_bp_modal.loadBaloonAjax(
                            false, window.awz_bp_modal.lastSign,
                            $('#AWZ_BPPOINT_INFO'), $('#AWZ_BPPOINT_ID').val(),
                            function(){
                                $('#AWZ_BPPOINT_INFO .awz-bp-select-pvz').remove();
                            }
                        );
                    }
                }
                window.awz_bp_modal.hide();
            });
        },
        setPickpointToOrder: function(){
            //awz-belpost-send-id
            var params = $('#awz-belpost-send-id-sign').val();
            $.ajax({
                url: '/bitrix/services/main/ajax.php?action=awz:belpost.api.pickpoints.setorder',
                method: 'POST',
                data: {
                    signed: params,
                    point: $('#AWZ_BPPOINT_ID').val()
                },
                success: function(resp){
                    if(resp.status == 'success'){
                        $('#awz-belpost-send-id-form').append('<p class="result-ajax" style="color:green;">'+resp.data+'</p>');
                    }else{
                        $('#awz-belpost-send-id-form').append('<p class="result-ajax" style="color:red;">'+resp.errors+'</p>');
                    }
                },
                error: function(){

                }
            });

        },
        getPoints: function(payment, type){
            var objectsArray = [];
            var k;
            for(k in window.awz_bp_modal.last_items){
                var item = window.awz_bp_modal.last_items[k];
                if(payment && payment.length){
                    var k2;
                    var checkPayment = false;
                    for(k2 in payment){
                        if(item.payment_methods.indexOf(payment[k2])>-1) checkPayment = true;
                    }
                    if(!checkPayment) continue;
                }
                if(type && type.length){
                    var k2;
                    var checkType = false;
                    for(k2 in type){
                        if(item.type.indexOf(type[k2])>-1) checkType = true;
                    }
                    if(!checkType) continue;
                }

                objectsArray.push({
                    "type": "Feature",
                    "id": item.id,
                    "geometry": {
                        "type": "Point",
                        "coordinates": [item.position.latitude,item.position.longitude]
                    },
                    "options":{
                        iconLayout: 'default#image',
                        iconImageHref: "/bitrix/images/awz.belpost/point.png",
                        iconImageSize: [32, 42],
                        iconImageOffset: [-16, -42],
                        preset: 'islands#blackClusterIcons',
                        openEmptyBalloon: true
                    },
                    "properties":{
                        balloonContent: '',
                        id: item.id
                    }
                });
            }
            return objectsArray;
        },
        getPickpointsList: function(params){
            //console.log(params);

            $('.awz-bp-modal-body').append(window.awz_bp_modal.loader_template());
            //debugger;

            var serv_error = window.BX ? window.BX.message('AWZ_BELPOST_JS_SERV_ERR') : 'server error';
            var choise_msg = window.BX ? window.BX.message('AWZ_BELPOST_JS_CHOISE') : 'choise';

            $.ajax({
                url: '/bitrix/services/main/ajax.php?action=awz:belpost.api.pickpoints.list',
                method: 'POST',
                data: {
                    signed: params
                },
                success: function(resp){
                    var data = resp.data;
                    window.awz_bp_modal.hideLoader();
                    if(data && data.hasOwnProperty('items')){

                        ymaps.ready(function(){
                            window.awz_bp_modal.initMap();

                            var customBalloonContentLayout = ymaps.templateLayoutFactory.createClass('<div class="yd-popup-balloon-content"></div>');
                            window.awz_bp_modal.objectManager = new ymaps.ObjectManager({
                                clusterize: true,
                                clusterBalloonContentLayout: customBalloonContentLayout,
                                geoObjectOpenBalloonOnClick: false
                            });
                            window.awz_bp_modal.objectManager.clusters.options.set('preset', 'islands#blackClusterIcons');
                            window.awz_bp_modal.objectManager.clusters.events.add(['balloonopen'], function(e){
                                //console.log(e);
                            });
                            window.awz_bp_modal.objectManager.objects.events.add(['click'], function(e){
                                window.awz_bp_modal.loadBaloonAjax(e, params);
                            });

                            window.awz_bp_modal.last_items = data.items;

                            var objectsArray = window.awz_bp_modal.getPoints();

                            window.awz_bp_modal.objectManager.add(objectsArray);

                            window.awz_bp_modal.map.geoObjects.add(window.awz_bp_modal.objectManager);

                            window.awz_bp_modal.map.setBounds(window.awz_bp_modal.map.geoObjects.getBounds(), {checkZoomRange:true});
                            //window.awz_bp_modal.map.setZoom(window.awz_bp_modal.map.getZoom()-2);

                        });
                    }else if(resp.status === 'error'){
                        var msg = '';
                        var k;
                        for(k in resp.errors){
                            var err = resp.errors[k];
                            msg += err.message+'<br><br>';
                        }
                        window.awz_bp_modal.setError(msg);
                    }
                },
                error: function(){
                    window.awz_bp_modal.setError(serv_error);
                }
            });

        }
    };
}

$(document).ready(function(){
    //console.log('register module awz.belpost');
    window.awz_bp_modal.initHandlers();
});