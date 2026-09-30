const ST={received:'تم استلام الطلب',review:'قيد المراجعة',accepted:'تم قبول الطلب',preparing:'قيد التجهيز',ready:'جاهز',awaiting_payment:'في انتظار الدفع',paid:'تم الدفع',closed:'تم إغلاق الطلب',rejected:'مرفوض'};
const PM={cash:'نقدي',card:'بطاقة',transfer:'تحويل',online:'دفع إلكتروني',other:'أخرى'};
const $=s=>document.querySelector(s);let U;
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const money=n=>Number(n).toFixed(3)+' د.ل';
async function api(a,d,q=''){const r=await fetch(API+'?a='+a+q,{method:d?'POST':'GET',headers:{'Content-Type':'application/json'},body:d?JSON.stringify(d):undefined});const j=await r.json().catch(()=>({error:'خطأ في الاتصال'}));if(r.status==401&&a!='me')location.reload();else if(j.error&&d&&a!='login')alert(j.error);return j}
function beep(){try{const c=new AudioContext(),o=c.createOscillator();o.connect(c.destination);o.frequency.value=880;o.start();setTimeout(()=>o.stop(),450)}catch(e){}}
async function boot(start){const m=await api('me');if(m.name){U=m;start()}else{window._s=start;$('#app').innerHTML=`<form class=login onsubmit="return doLogin(event)"><h2>تسجيل الدخول</h2><input id=u placeholder="اسم المستخدم" autocomplete=username required><input id=p type=password placeholder="كلمة المرور" autocomplete=current-password required><button style="width:100%;margin-top:8px">دخول</button><p id=err style="color:var(--red)"></p></form>`}}
async function doLogin(e){e.preventDefault();const r=await api('login',{username:$('#u').value,password:$('#p').value});if(r.name){U=r;_s()}else $('#err').textContent='اسم المستخدم أو كلمة المرور غير صحيحة';return false}
const can=p=>U.perms.includes('*')||U.perms.includes(p);
