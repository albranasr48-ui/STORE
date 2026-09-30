let M,cart={},step=0,tbl='',note='',trk=JSON.parse(localStorage.ord||'null'),tm;
const all=()=>M.cats.flatMap(c=>c.items),total=()=>all().reduce((s,i)=>s+(cart[i.id]||0)*i.price,0);
(async()=>{M=await api('menu');render();if(trk)track()})();
function add(id,d){cart[id]=Math.max(0,(cart[id]||0)+d);if(!cart[id])delete cart[id];render();if($('#sheet').classList.contains('on'))sheet()}
function render(){
 $('#nav').innerHTML=M.cats.map(c=>`<a href="#c${c.id}">${esc(c.name)}</a>`).join('');
 $('#menu').innerHTML=M.cats.length?M.cats.map(c=>`<h2 id=c${c.id}>${esc(c.name)}</h2><div class=grid>`+c.items.map(i=>`<div class=card>${i.image?`<img class=im src="${esc(i.image)}" alt="">`:'<div class=ph>🍽️</div>'}<div class=b><b>${esc(i.name)}</b><p>${esc(i.descr)}</p><div class=row><span class=pr>${money(i.price)}</span><span class=qty><button class=s onclick="add(${i.id},-1)">−</button><b>${cart[i.id]||0}</b><button onclick="add(${i.id},1)">+</button></span></div></div></div>`).join('')+'</div>').join(''):'<p>لا توجد أصناف بعد.</p>';
 const n=Object.values(cart).reduce((a,b)=>a+b,0);$('#fab').style.display=n?'flex':'none';$('#fab').textContent=`عرض الطلب (${n}) — ${money(total())}`}
function sheet(){const s=$('#sheet'),L=all().filter(i=>cart[i.id]);if(!L.length){s.classList.remove('on');return}
 s.classList.add('on');
 s.innerHTML=step==0?`<div class=pn><h3>سلة الطلب</h3>${L.map(i=>`<div class=row><span>${esc(i.name)}</span><span class=qty><button class=s onclick="add(${i.id},-1)">−</button><b>${cart[i.id]}</b><button onclick="add(${i.id},1)">+</button></span><b>${money(i.price*cart[i.id])}</b><button class=s onclick="add(${i.id},-99)">حذف</button></div>`).join('')}
 <div class=row><b>الإجمالي</b><b>${money(total())}</b></div>
 <select id=tb onchange="tbl=this.value"><option value="">اختر رقم الطاولة</option>${M.tables.map(t=>`<option value=${t} ${t==tbl?'selected':''}>طاولة ${t}</option>`).join('')}</select>
 <textarea id=nt rows=2 placeholder="ملاحظات على الطلب (اختياري)" oninput="note=this.value">${esc(note)}</textarea>
 <div class=row><button onclick="if(!tbl)return alert('اختر رقم الطاولة أولًا');step=1;sheet()">تأكيد وإرسال الطلب</button><button class=s onclick="$('#sheet').classList.remove('on')">متابعة الطلب</button></div></div>`
 :`<div class=pn><h3>مراجعة الطلب</h3><p>طاولة رقم <b>${tbl}</b></p>${L.map(i=>`<div class=row><span>${esc(i.name)} × ${cart[i.id]}</span><b>${money(i.price*cart[i.id])}</b></div>`).join('')}
 <div class=row><b>الإجمالي</b><b>${money(total())}</b></div>${note?`<p>ملاحظات: ${esc(note)}</p>`:''}
 <div class=row><button onclick="send()">إرسال الطلب</button><button class=s onclick="step=0;sheet()">رجوع</button></div></div>`}
async function send(){const r=await api('order_create',{table:+tbl,note,items:Object.entries(cart).map(([id,qty])=>({id:+id,qty}))});
 if(!r.id)return alert(r.error||'تعذر الإرسال');trk=r;localStorage.ord=JSON.stringify(r);cart={};note='';step=0;$('#sheet').classList.remove('on');render();track()}
function done(){localStorage.removeItem('ord');trk=null;$('#trk').classList.remove('on')}
async function track(){clearTimeout(tm);const r=await api('order_status',null,`&id=${trk.id}&t=${trk.token}`);
 if(!r.status){done();return}
 const fl=['received','review','preparing','ready','awaiting_payment','paid','closed'],cur=r.status=='accepted'?2:fl.indexOf(r.status),end=['closed','rejected'].includes(r.status);
 $('#trk').classList.add('on');
 $('#trk').innerHTML=`<div class=pn><h3>رقم طلبك #${r.id} — طاولة ${r.table_num}</h3><p>الإجمالي: <b>${money(r.total)}</b></p>`+
 (r.status=='rejected'?`<h2 style="color:var(--red)">نعتذر، تم رفض طلبك من قبل المطعم</h2>${r.reject_reason?`<p>السبب: ${esc(r.reject_reason)}</p>`:''}`:
 `<div class=steps>${fl.map((k,i)=>`<div class="${i<cur?'d':i==cur?'c':''}">${i<cur?'✓':i==cur?'●':'○'} ${ST[k]}</div>`).join('')}</div>`)+
 `<div class=row>${end||r.status=='paid'?'<button onclick="done()">طلب جديد</button>':''}<button class=s onclick="$('#trk').classList.remove('on')">عرض القائمة</button></div></div>`;
 if(!end)tm=setTimeout(track,3000)}
