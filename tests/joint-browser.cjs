'use strict';
const fs=require('node:fs'),assert=require('node:assert/strict');
const {chromium}=require('playwright');
(async()=>{
  const config=fs.readFileSync('private/config.php','utf8');assert(config.includes("'local'")&&config.includes('_test'));
  const [day,email]=process.argv.slice(2);const base='http://127.0.0.1:8089/';let n=0;const check=(ok,label)=>{assert(ok,label);n++;};
  const browser=await chromium.launch({executablePath:process.env.BROWSER_EXECUTABLE||'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless:true});
  try {
    const context=await browser.newContext();const p=await context.newPage();fs.mkdirSync('.test-data/joint',{recursive:true});
    const unauth=await context.request.get(base+'?page=availability&room=1&day='+day+'&start=09:20');check(unauth.status()===403,'anonymous preview denied');
    await p.goto(base+'?page=login');await p.locator('[name=email]').fill(email);await p.locator('[name=password]').fill('Contraseña ficticia prueba conjunta');await p.getByRole('button',{name:'Iniciar sesión',exact:true}).click();
    check(await p.locator('select[name=room]').inputValue()==='0','initial all rooms');
    for(const publicView of [false,true])for(const width of [1280,390])for(const view of ['week','day','month']) {
      await p.setViewportSize({width,height:900});await p.goto(base+(publicView?'ocupacion.php?':'?page=agenda&')+'view='+view+'&room=0&day='+day);await p.evaluate(()=>document.fonts.ready);
      check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'responsive '+view);
      check(await p.locator('.room-color-legend .room-color').count()===2,'room legend');
      if(view==='week') {
        check(await p.locator('.occupation-column').count()===5&&await p.locator('.occupation-lane').count()===10,'five days two lanes');
        const a=p.locator('.room-color-1 .occupation-event').filter({hasText:'09:10'}),b=p.locator('.room-color-3 .occupation-event').filter({hasText:'09:30'});
        const ra=await a.boundingBox(),rb=await b.boundingBox();check(ra.x+ra.width<=rb.x+1,'partial overlap side by side');check(Math.abs(rb.y-ra.y-30)<2,'exact starts twenty minutes apart');
        const ca=await a.evaluate(e=>getComputedStyle(e).backgroundColor),cb=await b.evaluate(e=>getComputedStyle(e).backgroundColor);check(ca!==cb,'different room colors');
        const sameA=await p.locator('.room-color-1 .occupation-event').filter({hasText:'13:00'}).boundingBox(),sameB=await p.locator('.room-color-3 .occupation-event').filter({hasText:'13:00'}).boundingBox();check(Math.abs(sameA.y-sameB.y)<1&&sameA.x+sameA.width<=sameB.x+1,'simultaneous events do not overlap');
      }
      if(view==='day')check(await p.locator('.occupation-column').count()===2,'daily room columns');
      if(view==='month')check(await p.locator('.month-cell[href*="day='+day+'"]').locator('.month-room').count()===2,'monthly each room summary');
      if(publicView)check(!(await p.content()).includes('PRUEBA CONJUNTA')&&!(await p.content()).includes(email)&&await p.locator('[data-free-slot]').count()===0,'public privacy and read only');
      await p.screenshot({path:'.test-data/joint/'+(publicView?'public':'private')+'-'+view+'-'+width+'.png',fullPage:true});
    }
    await p.goto(base+'?page=agenda&view=week&room=0&day='+day);
    const slot=p.locator('[data-free-slot][data-day="'+day+'"][data-start="09:00"][data-room-id="0"]').last();await slot.focus();await p.keyboard.press('Enter');
    check(await p.locator('#room_id').inputValue()===''&&await p.locator('#booking-end').inputValue()==='','joint slot has no room or proposed duration');
    check(await p.locator('#room_id').evaluate(e=>e===document.activeElement),'focus explicit room');
    await p.locator('#booking-start').fill('09:20');await p.locator('#room_id').selectOption('1');await p.waitForFunction(()=>document.getElementById('slot-message').textContent.includes('ocupada'));
    check((await p.locator('#slot-message').innerText()).includes('ocupada'),'selected busy room preview');
    await p.locator('#room_id').selectOption('3');await p.waitForFunction(()=>document.getElementById('slot-message').textContent.includes('No hay 30 minutos'));
    check(await p.locator('#booking-end').inputValue()==='','short available gap no duration');
    await p.locator('#booking-start').fill('07:30');await p.locator('#booking-start').dispatchEvent('change');await p.waitForFunction(()=>document.getElementById('booking-end').value==='08:00');check(true,'thirty minutes after availability check');
    const preview=await context.request.get(base+'?page=availability&room=3&day='+day+'&start=09:20');const json=await preview.json();check(Object.keys(json).sort().join(',')==='available,message,proposed_end','preview response minimal');
    check((await p.locator('#public-link').inputValue()).includes('room=0')&&(await p.locator('#public-iframe').inputValue()).includes('room=0'),'sharing all rooms');
    await p.goto(base+'?page=agenda&view=week&room=3&day='+day);check(await p.locator('.occupation-lane').count()===5,'individual room filter');
    const single=p.locator('[data-free-slot]:not([disabled])').first();await single.focus();await p.keyboard.press('Enter');check(await p.locator('#room_id').inputValue()==='3','single room slot retained');
    await p.goto(base+'ocupacion.php?view=month&room=0&day='+day);await p.locator('.month-cell[href*="day='+day+'"]').click();check(p.url().includes('room=0')&&p.url().includes('view=day'),'public month to day retains all');
    console.log('JOINT BROWSER: '+n+' comprobaciones correctas de simultaneidad, consulta conjunta, privacidad y teclado.');
  } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exit(1);});
