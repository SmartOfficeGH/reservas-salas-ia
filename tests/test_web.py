"""Recorrido HTTP contra el servidor local. No envía correos ni accede al hosting."""
import http.cookiejar, urllib.request, urllib.parse, urllib.error, re, json, pathlib, secrets, subprocess
from datetime import datetime, timedelta
from zoneinfo import ZoneInfo

BASE='http://127.0.0.1:8089/'
ROOT=pathlib.Path(__file__).resolve().parent.parent
config=(ROOT/'private/config.php').read_text(encoding='utf-8')
assert "'local'" in config and '_test' in config, 'Solo configuración local de pruebas'
count=0
def check(condition,label):
    global count
    if not condition: raise AssertionError(label)
    count+=1
class Client:
    def __init__(self):
        self.jar=http.cookiejar.CookieJar()
        self.opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar))
    def request(self,path='',data=None):
        req=urllib.request.Request(BASE+path,data=None if data is None else urllib.parse.urlencode(data).encode())
        try: result=self.opener.open(req,timeout=15)
        except urllib.error.HTTPError as e: result=e
        return result.status,result.read().decode(),result.headers,result.geturl()
    def post(self,page,action,**data):
        _,body,_,_=self.request('?page='+page)
        csrf=re.search(r'name="csrf" value="([^"]+)"',body).group(1)
        return self.request('?page='+page,dict(action=action,csrf=csrf,**data))
def token(email,kind):
    matches=[]
    for file in (ROOT/'private/storage/test-mail').glob('*.json'):
        message=json.loads(file.read_text(encoding='utf-8'))
        if message['email']==email and message['kind']==kind: matches.append((file.stat().st_mtime_ns,message['token']))
    return sorted(matches)[-1][1]

c=Client(); other=Client(); suffix=secrets.token_hex(5)
email='http.'+suffix+'@palma.es'; email2='http.other.'+suffix+'@palma.es'; pw='Contraseña ficticia larga'
status,body,headers,_=c.request()
check('Iniciar sesión' in body and 'Coordinación' not in body,'anonymous cannot see agenda')
check("frame-ancestors 'none'" in headers['Content-Security-Policy'],'CSP')
check('no-store' in headers['Cache-Control'],'no caching')
status,body,_,_=c.request('?page=register',{'action':'register','csrf':'bad','email':email,'password':pw})
check(status==403 and 'formulario ha caducado' in body,'CSRF blocks registration')
_,body,_,_=c.post('register','register',email='bad@sub.palma.es',password=pw)
check('exactamente palma.es' in body,'exact domain enforced HTTP')
_,body,_,_=c.post('register','register',email=email,password=pw)
check('enlace de verificación' in body,'registration')
_,body,_,_=c.post('login','login',email=email,password=pw)
check('Verifica tu correo' in body,'unverified cannot login')
captured=[json.loads(f.read_text()) for f in (ROOT/'private/storage/test-mail').glob('*.json')]
check(any(m['email']==email and m.get('from_name')=='Aplicación Reserva Salas' and 'Aplicación Reserva Salas' in m.get('subject','') and 'Aplicación Reserva Salas' in m.get('body','') for m in captured),'local verification mail name')
raw=token(email,'verify'); _,body,_,url=c.request('?page=verify&token='+raw)
check('token=' not in url and raw not in body,'token URL scrubbed')
_,body,_,_=c.post('verify','verify')
check('Correo verificado' in body,'verification')
c.request('?page=verify&token='+raw); _,body,_,_=c.post('verify','verify')
check('inválido o caducado' in body,'one-use verification')
_,body,_,_=c.post('login','login',email=email,password=pw)
check('SALA GRANDE INNOVACIÓN' in body and '18 personas' in body and 'Normativa de uso' in body,'login agenda and approved data')
check('SALA PEQUEÑA INNOVACIÓN' not in body and body.count('<article class="room">')==2,'only two room cards')
check('Ocupación de salas</h2>' in body and body.count('class="occupation-column"')==5,'initial weekly occupancy')
check(body.index('Salas y disponibilidad</h2>')<body.index('Nueva reserva</h2>')<body.index('Ocupación de salas</h2>')<body.index('Normativa de uso</h2>'),'approved page order')
today=datetime.now(ZoneInfo("Europe/Madrid")).date()
check(f'name="day" value="{today}"' in body and 'value="week"' in body,'initial current Madrid week')
for explicit,columns in [('day',1),('week',5),('month',0)]:
    _,selected,_,_=c.request(f'?page=agenda&view={explicit}&room=3')
    check(selected.count('class="occupation-column"')==columns and f'view={explicit}&amp;room=3' in selected,'explicit view and filter '+explicit)
check(body.count('class="detail-icon"')==6 and body.count('alt="" aria-hidden="true"')==6,'decorative room icons preserve labels')
options=re.search(r'<select id="room_id".*?</select>',body,re.S).group(0)
check(re.findall(r'<option value="(\d+)"',options)==['1','3'],'only two form options')
check(body.count('data-carousel-slide')==2 and body.index('data-carousel>')<body.index('Salas y disponibilidad</h2>'),'compact carousel above agenda')
check('aria-label="Ver sala anterior"' in body and 'aria-label="Ver sala siguiente"' in body,'accessible carousel buttons')
for link in ['https://maps.app.goo.gl/eUbJNMvrXMbCu2Gn7','https://maps.app.goo.gl/kTES8GwQxbLs96GX9']:
    check(f'href="{link}" target="_blank" rel="noopener noreferrer"' in body,'approved location link')
for image in ['images/sala-grande-innovacion.jpeg','images/sala-otae.jpg']:
    result=c.opener.open(BASE+image)
    check(result.status==200 and result.read(3)==b'\xff\xd8\xff','JPEG served')
check(any(cookie.has_nonstandard_attr('HttpOnly') for cookie in c.jar),'HttpOnly session')
day=datetime.now(ZoneInfo('Europe/Madrid')).date()+timedelta(days=1)
while day.weekday()>4: day+=timedelta(days=1)
booking=dict(room_id='1',concept='HTTP '+suffix,day=str(day),start='14:00',end='15:00')
_,body,_,_=c.post('agenda','book',**(booking|{'room_id':'2'}))
check('no está disponible para nuevas reservas' in body,'retired room server rejection')
_,body,_,_=c.post('agenda','book',**booking)
check('Reserva confirmada' in body and 'HTTP '+suffix in body,'create and persistent agenda')
check('Ocupado · 14:00–15:00' in body and 'class="event ' not in body,'booking appears once as occupancy block, no second agenda')
_,weekly,_,_=c.request(f'?page=agenda&day={day}&view=week&room=1')
check(weekly.count('class="occupation-column"')==5 and 'HTTP '+suffix in weekly,'weekly booking and five columns')
_,weekly_other,_,_=c.request(f'?page=agenda&day={day}&view=week&room=3')
check('HTTP '+suffix not in weekly_other and 'data-room-id="3"' in weekly_other,'weekly filter')
_,monthly,_,_=c.request(f'?page=agenda&day={day}&view=month&room=1')
check('1 reservas' in monthly and 'data-free-slot' not in monthly,'monthly summary without slots')
check(f'day={day}&amp;view=day&amp;room=1' in monthly,'monthly daily link')
_,month_end,_,_=c.request('?page=agenda&day=2027-01-31&view=month&room=3')
check('day=2027-02-28&amp;view=month&amp;room=3' in month_end,'month navigation clamped and filter preserved')
check(f'day={datetime.now(ZoneInfo("Europe/Madrid")).date()}&amp;view=month&amp;room=3' in month_end,'today preserves view and room')
_,body,_,_=c.post('agenda','book',**booking)
check('ya está reservada' in body,'HTTP overlap')
for changes,expected in [({'start':'06:59'},'07:00'),({'end':'14:00'},'posterior'),({'day':'2020-01-06'},'pasado')]:
    _,body,_,_=c.post('agenda','book',**(booking|changes));check(expected in body,'server validation '+expected)
_,body,_,_=c.request('?page=mine')
check('HTTP '+suffix in body,'own list');rid=re.search(r'name="reservation_id" value="(\d+)"',body).group(1)
other.post('register','register',email=email2,password=pw);other.request('?page=verify&token='+token(email2,'verify'));other.post('verify','verify');other.post('login','login',email=email2,password=pw)
_,body,_,_=other.request('?page=agenda&day='+str(day));check('HTTP '+suffix in body,'other authenticated user sees concepts')
_,body,_,_=other.request('?page=mine');check('HTTP '+suffix not in body,'other cannot list private reservations')
_,body,_,_=other.post('mine','cancel',reservation_id=rid);check('no te pertenece' in body,'tampered cancellation denied')
_,body,_,_=c.post('mine','cancel',reservation_id=rid);check('Reserva cancelada' in body,'own cancellation')
_,body,_,_=c.request('?page=agenda&day='+str(day));check('HTTP '+suffix not in body,'cancellation releases agenda')
resetter=Client();resetter.post('forgot','forgot',email=email);raw=token(email,'reset');resetter.request('?page=reset&token='+raw)
_,body,_,_=resetter.post('reset','reset',password='Nueva contraseña ficticia');check('Contraseña actualizada' in body,'reset')
_,body,_,_=c.request('?page=mine');check('Iniciar sesión' in body,'existing session revoked')
_,body,_,_=c.post('login','login',email=email,password=pw);check('incorrectos' in body,'old password denied')
c.post('login','login',email=email,password='Nueva contraseña ficticia')
for mode in ['idle','absolute']:
    sid=next(cookie.value for cookie in c.jar if cookie.name=='reservas_session')
    _,body,_,_=c.request('?page=agenda')
    old_csrf=re.search(r'name="csrf" value="([^"]+)"',body).group(1)
    subprocess.run([str(ROOT/'.tools/php/php.exe'),str(ROOT/'tests/age-session.php'),sid,mode],check=True,cwd=ROOT)
    _,body,_,_=c.request('?page=mine')
    check('Iniciar sesión' in body,mode+' timeout')
    new_sid=next(cookie.value for cookie in c.jar if cookie.name=='reservas_session')
    check(sid!=new_sid,mode+' timeout renews session id')
    status,body,_,_=c.request('?page=agenda',dict(action='book',csrf=old_csrf,**booking))
    check(status==403,mode+' expired form blocked')
    c.post('login','login',email=email,password='Nueva contraseña ficticia')
_,body,_,_=c.post('agenda','logout');check('Iniciar sesión' in body,'logout')
print(f'HTTP: {count} comprobaciones correctas; transporte de correo local, sin SMTP.')
