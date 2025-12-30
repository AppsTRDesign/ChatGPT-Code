(function($){
    const toast = (msg,type='success')=> toastr[type](msg);
    const ajax = (url, method='GET', data=null)=> $.ajax({url, method, data, dataType:'json'});

    function loadStats(){
        ajax('api/stats.php').done(res=>{
            const row = $('#stats-row');
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

    function loadClaims(){
        ajax('api/claims.php').done(res=>{
            const tbody = $('#claims-table tbody');
            tbody.empty();
            res.items.forEach(item=>{
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
        });
    }

    function loadReviews(){
        ajax('api/reviews.php').done(res=>{
            const tbody = $('#reviews-table tbody').empty();
            res.items.forEach(r=>{
                const actions = r.status==='pending' ? `
                    <button class="btn btn-success btn-sm me-1" data-review="approve" data-id="${r.id}">Onayla</button>
                    <button class="btn btn-danger btn-sm" data-review="reject" data-id="${r.id}">Reddet</button>` : '';
                tbody.append(`<tr>
                    <td>${r.id}</td><td>${r.place}</td><td>${r.author}</td><td>${r.rating}</td><td>${r.status}</td><td class="text-truncate" style="max-width:240px;">${r.text||''}</td><td>${actions}</td>
                </tr>`);
            });
        });
    }

    function loadUsers(){
        ajax('api/users.php').done(res=>{
            const tbody = $('#users-table tbody').empty();
            res.items.forEach(u=>{
                tbody.append(`<tr>
                    <td>${u.id}</td><td>${u.name}</td><td>${u.email}</td>
                    <td><select class="form-select form-select-sm user-role" data-id="${u.id}">
                        ${['admin','business_owner','user'].map(role=>`<option value="${role}" ${role===u.role?'selected':''}>${role}</option>`).join('')}</select></td>
                    <td><select class="form-select form-select-sm user-status" data-id="${u.id}">
                        ${['active','pending','banned'].map(st=>`<option value="${st}" ${st===u.status?'selected':''}>${st}</option>`).join('')}</select></td>
                    <td><button class="btn btn-primary btn-sm" data-user-update="${u.id}">Kaydet</button></td>
                </tr>`);
            });
        });
    }

    $(document).on('click','[data-claim]',function(){
        const id=$(this).data('id');
        const action=$(this).data('claim');
        ajax('api/claims.php','POST',{action,id}).done(res=>{toast(res.message);loadClaims();loadStats();});
    });

    $(document).on('click','[data-review]',function(){
        const id=$(this).data('id');
        const action=$(this).data('review');
        ajax('api/reviews.php','POST',{action,id}).done(res=>{toast(res.message);loadReviews();loadStats();});
    });

    $(document).on('click','[data-user-update]',function(){
        const id=$(this).data('user-update');
        const role=$(`select.user-role[data-id=${id}]`).val();
        const status=$(`select.user-status[data-id=${id}]`).val();
        ajax('api/users.php','POST',{id,role,status}).done(res=>{toast(res.message);loadUsers();});
    });

    loadStats();
    loadClaims();
    loadReviews();
    loadUsers();
})(jQuery);
