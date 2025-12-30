(function($){
    const toast = (msg,type='success')=> toastr[type](msg);
    const ajax = (url, method='GET', data=null)=> $.ajax({url, method, data, dataType:'json'});

    const page = $('body').data('page');

    function renderPagination(meta, target, onClick){
        const $el = $(target);
        $el.empty();
        if (!meta || meta.pages <= 1) return;
        const add = (p,label,disabled=false,active=false)=>{
            const li = $('<li class="page-item"></li>');
            if(disabled) li.addClass('disabled');
            if(active) li.addClass('active');
            const a = $('<a class="page-link" href="#"></a>').text(label).data('page', p);
            li.append(a);
            $el.append(li);
        };
        add(meta.page-1,'«',meta.page<=1);
        for(let i=1;i<=meta.pages;i++){
            add(i,i,false, i===meta.page);
        }
        add(meta.page+1,'»',meta.page>=meta.pages);
        $el.off('click','a').on('click','a',function(e){
            e.preventDefault();
            const p = $(this).data('page');
            if(!p || p<1 || p>meta.pages) return;
            onClick(p);
        });
    }

    function loadStats(){
        ajax('api/stats.php').done(res=>{
            const row = $('#stats-row');
            if(!row.length) return;
            row.empty();
            const cards = [
                {title:'İşletme', value:res.places||0, cls:'primary'},
                {title:'Bekleyen Claim', value:res.pending_claims||0, cls:'warning'},
                {title:'Bekleyen Yorum', value:res.pending_reviews||0, cls:'info'},
                {title:'Kullanıcı', value:res.users||0, cls:'secondary'}
            ];
            cards.forEach(c=>{
                row.append(`<div class="col-sm-6 col-md-3"><div class="card border-${c.cls} shadow-sm"><div class="card-body"><p class="text-muted mb-1">${c.title}</p><h4 class="mb-0">${c.value}</h4></div></div></div>`);
            });
        });
    }

    // Claims
    const claimState = {page:1, per_page:10, q:'', status:''};
    function loadClaims(){
        const params = Object.assign({}, claimState);
        ajax('api/claims.php','GET',params).done(res=>{
            const tbody = $('#claims-table tbody');
            if(!tbody.length) return;
            tbody.empty();
            (res.items||[]).forEach(item=>{
                const actions = item.status==='pending' ? `
                    <button class="btn btn-success btn-sm me-1" data-claim="approve" data-id="${item.id}">Onayla</button>
                    <button class="btn btn-danger btn-sm" data-claim="reject" data-id="${item.id}">Reddet</button>` : '';
                tbody.append(`<tr>
                    <td>${item.id}</td>
                    <td>${item.place}</td>
                    <td>${item.user}</td>
                    <td><span class="badge bg-${item.status==='approved'?'success': item.status==='rejected'?'danger':'warning'}">${item.status}</span></td>
                    <td>${item.approval_method}</td>
                    <td>${item.created_at}</td>
                    <td>${actions}</td>
                </tr>`);
            });
            renderPagination(res.meta || {page:1,pages:1}, '#claims-pagination', (p)=>{claimState.page=p;loadClaims();});
        });
    }

    // Reviews
    const reviewState = {page:1, per_page:10, q:'', status:''};
    function loadReviews(){
        const params = Object.assign({}, reviewState);
        ajax('api/reviews.php','GET',params).done(res=>{
            const tbody = $('#reviews-table tbody');
            if(!tbody.length) return;
            tbody.empty();
            (res.items||[]).forEach(r=>{
                const actions = r.status==='pending' ? `
                    <button class="btn btn-success btn-sm me-1" data-review="approve" data-id="${r.id}">Onayla</button>
                    <button class="btn btn-danger btn-sm" data-review="reject" data-id="${r.id}">Reddet</button>` : '';
                tbody.append(`<tr>
                    <td>${r.id}</td><td>${r.place}</td><td>${r.author}</td><td>${r.rating}</td><td><span class="badge bg-${r.status==='approved'?'success':r.status==='rejected'?'danger':'warning'}">${r.status}</span></td><td class="text-truncate" style="max-width:260px;">${r.text||''}</td><td>${actions}</td>
                </tr>`);
            });
            renderPagination(res.meta || {page:1,pages:1}, '#reviews-pagination', (p)=>{reviewState.page=p;loadReviews();});
        });
    }

    // Users
    const userState = {page:1, per_page:10, q:''};
    function loadUsers(){
        const params = Object.assign({}, userState);
        ajax('api/users.php','GET',params).done(res=>{
            const tbody = $('#users-table tbody');
            if(!tbody.length) return;
            tbody.empty();
            (res.items||[]).forEach(u=>{
                tbody.append(`<tr>
                    <td>${u.id}</td><td>${u.name}</td><td>${u.email}</td>
                    <td><select class="form-select form-select-sm user-role" data-id="${u.id}">
                        ${['admin','business_owner','user'].map(role=>`<option value="${role}" ${role===u.role?'selected':''}>${role}</option>`).join('')}</select></td>
                    <td><select class="form-select form-select-sm user-status" data-id="${u.id}">
                        ${['active','pending','banned'].map(st=>`<option value="${st}" ${st===u.status?'selected':''}>${st}</option>`).join('')}</select></td>
                    <td><button class="btn btn-primary btn-sm" data-user-update="${u.id}">Kaydet</button></td>
                </tr>`);
            });
            renderPagination(res.meta || {page:1,pages:1}, '#users-pagination', (p)=>{userState.page=p;loadUsers();});
        });
    }

    $(document).on('click','[data-claim]',function(){
        const id=$(this).data('id');
        const action=$(this).data('claim');
        ajax('api/claims.php','POST',{action,id}).done(res=>{toast(res.message);claimState.page=1;loadClaims();loadStats();});
    });

    $(document).on('click','[data-review]',function(){
        const id=$(this).data('id');
        const action=$(this).data('review');
        ajax('api/reviews.php','POST',{action,id}).done(res=>{toast(res.message);reviewState.page=1;loadReviews();loadStats();});
    });

    $(document).on('click','[data-user-update]',function(){
        const id=$(this).data('user-update');
        const role=$(`select.user-role[data-id=${id}]`).val();
        const status=$(`select.user-status[data-id=${id}]`).val();
        ajax('api/users.php','POST',{id,role,status}).done(res=>{toast(res.message);});
    });

    $('#claim-filters').on('submit',function(e){
        e.preventDefault();
        claimState.q = $(this).find('[name=q]').val();
        claimState.status = $(this).find('[name=status]').val();
        claimState.per_page = $(this).find('[name=per_page]').val();
        claimState.page = 1;
        loadClaims();
    });

    $('#review-filters').on('submit',function(e){
        e.preventDefault();
        reviewState.q = $(this).find('[name=q]').val();
        reviewState.status = $(this).find('[name=status]').val();
        reviewState.per_page = $(this).find('[name=per_page]').val();
        reviewState.page = 1;
        loadReviews();
    });

    $('#user-filters').on('submit',function(e){
        e.preventDefault();
        userState.q = $(this).find('[name=q]').val();
        userState.per_page = $(this).find('[name=per_page]').val();
        userState.page = 1;
        loadUsers();
    });

    if(page==='dashboard'){ loadStats(); }
    if(page==='claims'){ loadClaims(); loadStats(); }
    if(page==='reviews'){ loadReviews(); loadStats(); }
    if(page==='users'){ loadUsers(); loadStats(); }
})(jQuery);
