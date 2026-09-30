let ids=new Set(),f=true;
function start(){if(!can('kitchen')&&!can('orders')){$('#app').innerHTML='<p class=login>ليست لديك صلاحية المطبخ</p>';return}
 $('#app').innerHTML='<div class=row style="padding:10px 16px;background:var(--g);color:#fff"><h2 style="margin:0">🍳 شاشة المطبخ</h2><small>انقر في أي مكان لتفعيل الصوت</small></div><div class=kg id=kg></div>';poll();setInterval(poll,3000)}
async function poll(){const o=await api('orders');if(!Array.isArray(o))return;const L=o.filter(x=>['accepted','preparing'].includes(x.status)).reverse();
 if(!f&&L.some(x=>!ids.has(x.id)))beep();ids=new Set(L.map(x=>x.id));f=false;
 $('#kg').innerHTML=L.map(x=>`<div class=card><div class=row><h2>#${x.id}</h2><h2>طاولة ${x.table_num}</h2></div><div>🕒 ${x.created_at.slice(11,16)} <span class=tag>${ST[x.status]}</span></div><hr>${(x.items||[]).map(i=>`<div><b>${i.qty} ×</b> ${esc(i.name)}</div>`).join('')}${x.note?`<p>📝 <b>${esc(x.note)}</b></p>`:''}${x.status=='accepted'?`<button class=a onclick="setK(${x.id},'preparing')">بدء التجهيز</button>`:''}<button onclick="setK(${x.id},'ready')">تم التجهيز</button></div>`).join('')||'<p style="padding:16px">لا توجد طلبات قيد التحضير</p>'}
const setK=async(id,status)=>{await api('order_set',{id,status});poll()};
