(function($){
    const toast = (msg)=>{
        let el = document.getElementById('pro-ultra-toast');
        if(!el){
            el = document.createElement('div');
            el.id = 'pro-ultra-toast';
            el.className = 'pro-ultra-toast';
            document.body.appendChild(el);
        }
        el.textContent = msg;
        el.classList.add('is-visible');
        setTimeout(()=>el.classList.remove('is-visible'),3200);
    };

    function activateTab(tab){
        $('.pro-ultra-tab').removeClass('is-active');
        $('.pro-ultra-panel').removeClass('is-active');
        $(`.pro-ultra-tab[data-tab="${tab}"]`).addClass('is-active');
        $(`.pro-ultra-panel[data-panel="${tab}"]`).addClass('is-active');
        localStorage.setItem('proUltraActiveTab', tab);
    }

    function serializeForm($form){
        const data = {};
        const formData = new FormData($form[0]);
        for(const [key,value] of formData.entries()){
            if(key === 'security' || value instanceof File) continue;
            if(key.startsWith('blocks[')){
                const match = key.match(/blocks\[(.*?)\]\[(.*?)\]/);
                if(match){
                    const block = match[1];
                    const field = match[2];
                    data.blocks = data.blocks || {};
                    data.blocks[block] = data.blocks[block] || {};
                    data.blocks[block][field] = value;
                }
            }else if(key.startsWith('data[')){
                const inner = key.replace(/^data\[(.*)\]$/,'$1');
                if(inner.indexOf('][') !== -1){
                    const [first, second] = inner.split('][');
                    data[first] = data[first] || {};
                    data[first][second.replace(']','')] = value;
                }else{
                    data[inner] = value;
                }
            }else if(key.startsWith('strings[')){
                const match = key.match(/strings\[(.*?)\]\[(.*?)\]/);
                if(match){
                    const block = match[1];
                    const lang = match[2];
                    data.strings = data.strings || {};
                    data.strings[block] = data.strings[block] || {};
                    data.strings[block][lang.replace(']','')] = value;
                }
            }else{
                data[key] = value;
            }
        }
        if($form.data('section') === 'home_blocks'){
            const order = [];
            $('#pro-ultra-blocks').children().each(function(){
                order.push($(this).data('block'));
            });
            data.order = order;
        }
        return data;
    }

    function saveSection($form){
        const section = $form.data('section');
        const formData = new FormData();
        const nonce = $form.find('input[name="security"]').val();
        const payload = serializeForm($form);
        formData.append('action','pro_ultra_save_options');
        formData.append('security',nonce);
        formData.append('section',section);
        formData.append('data',JSON.stringify(payload));
        const fileInput = $form.find('input[type="file"]')[0];
        if(fileInput && fileInput.files && fileInput.files.length){
            formData.append('translation_file', fileInput.files[0]);
        }
        $form.addClass('is-saving');
        return fetch(proUltraOptionsData.ajaxUrl, {
            method:'POST',
            credentials:'same-origin',
            body: formData
        }).then(res=>res.json())
        .then(res=>{
            const message = res && res.data && res.data.message ? res.data.message : (res && res.success ? proUltraOptionsData.success : proUltraOptionsData.error);
            toast(message);
        })
        .catch(()=>toast(proUltraOptionsData.error))
        .finally(()=>{$form.removeClass('is-saving');});
    }

    function initSortables(){
        const $list = $('#pro-ultra-blocks');
        if(!$list.length) return;
        $list.sortable({
            handle: '.dashicons',
            placeholder: 'pro-ultra-sortable-placeholder',
            update: function(){
                saveSection($list.closest('form'));
            }
        });
    }

    function initTabs(){
        $('.pro-ultra-tab').on('click', function(){
            activateTab($(this).data('tab'));
        });
        const stored = localStorage.getItem('proUltraActiveTab') || proUltraOptionsData.section;
        activateTab(stored);
    }

    function initColorPickers(){
        if($.fn.wpColorPicker){
            $('.pro-ultra-color').wpColorPicker();
        }
    }

    function initRanges(){
        $(document).on('input change','.pro-ultra-range input[type="range"]',function(){
            $(this).siblings('.pro-ultra-range-value').text(`${this.value}${this.max>24?'':'px'}`);
        });
    }

    function initUploads(){
        $(document).on('click','.pro-ultra-upload',function(e){
            e.preventDefault();
            const target = $(this).closest('.pro-ultra-media').data('target');
            const frame = wp.media({title: $(this).data('title') || 'Upload', multiple:false});
            frame.on('select',()=>{
                const attachment = frame.state().get('selection').first().toJSON();
                const $wrap = $(this).closest('.pro-ultra-media');
                const thumb = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
                $wrap.find(`input[name="data[${target}]"]`).val(attachment.id);
                $wrap.find('.pro-ultra-preview').html(`<img src="${thumb}" alt="" />`);
            });
            frame.open();
        });
    }

    function initSaveButtons(){
        $('.pro-ultra-save').on('click', function(){
            saveSection($(this).closest('form'));
        });
        $(document).on('change','.pro-ultra-sortable input[type="checkbox"]',function(){
            saveSection($(this).closest('form'));
        });
    }

    function initExport(){
        $(document).on('click','.pro-ultra-export', function(e){
            e.preventDefault();
            const $form = $(this).closest('form');
            const payload = serializeForm($form);
            const blob = new Blob([JSON.stringify(payload.strings || {}, null, 2)], {type:'application/json'});
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'pro-ultra-translations.json';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            toast(proUltraOptionsData.success);
        });
    }

    function init(){
        initTabs();
        initSortables();
        initColorPickers();
        initRanges();
        initUploads();
        initSaveButtons();
        initExport();
    }

    $(document).ready(init);
})(jQuery);
