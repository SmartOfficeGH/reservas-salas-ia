'use strict';
const fs=require('node:fs');
const assert=require('node:assert/strict');
const http=require('node:http');
const {chromium}=require('playwright');
(async()=>{
  const config=fs.readFileSync('private/config.php','utf8');
  assert(config.includes("'local'")&&config.includes('_test'),'Solo pruebas locales');
  const day=process.argv[2]||'2026-10-06';
  const base='http://127.0.0.1:8089/';
  let checks=0;const check=(ok,label)=>{assert(ok,label);checks++;};
  const browser=await chromium.launch({executablePath:process.env.BROWSER_EXECUTABLE||'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless:true});
  let embedServer;
  try {
    const context=await browser.newContext();const page=await context.newPage();
    const errors=[];page.on('pageerror',e=>errors.push(e.message));
    fs.mkdirSync('.test-data/public',{recursive:true});
    for(const width of [1280,390]) {
      await page.setViewportSize({width,height:900});
      for(const view of ['day','week','month']) {
        await page.goto(base+'ocupacion.php?day='+day+'&view='+view+'&room=3');
        await page.evaluate(()=>document.fonts.ready);
        check(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'responsive '+view);
        check(await page.locator('[data-free-slot],#nueva-reserva,[name=concept],[name=csrf]').count()===0,'no booking controls');
        check(!(await page.content()).includes('@palma.es'),'no user emails');
        if(view!=='month') {
          const url=page.url();await page.locator('.occupation-gap').first().click();
          check(page.url()===url,'free area is read only');
          if(process.argv[2])check(await page.locator('.occupation-event').count()===1,'fixture occupation displayed');
        }
        await page.screenshot({path:'.test-data/public/'+view+'-'+width+'.png',fullPage:true});
        await page.getByRole('button',{name:'Actualizar',exact:true}).click();
        check(page.url().includes('view='+view)&&page.url().includes('room=3')&&page.url().includes('day='+day),'refresh preserves selection');
      }
    }
    await page.goto(base+'ocupacion.php?view=day&day='+day+'&room=all');
    check(await page.locator('.occupation-column').count()===2,'daily both rooms');
    await page.locator('select[name=room]').selectOption('1');await page.getByRole('button',{name:'Consultar',exact:true}).click();
    check(await page.locator('.occupation-column').count()===1,'daily filter');
    await page.goto(base+'ocupacion.php?view=month&day='+day+'&room=3');
    await page.locator('.month-cell[href*="day='+day+'"]').click();
    check(page.url().includes('view=day')&&page.url().includes('room=3'),'month opens daily');
    await page.getByRole('link',{name:'Periodo siguiente'}).click();
    check(page.url().includes('room=3'),'period navigation retains filter');
    await page.getByRole('link',{name:'Hoy',exact:true}).click();
    check(page.url().includes('view=day')&&page.url().includes('room=3'),'today retains state');
    embedServer=http.createServer((req,res)=>{res.setHeader('Content-Type','text/html; charset=utf-8');const src=req.url==='/protected'?base+'?page=agenda':base+'ocupacion.php?view=day&amp;day='+day+'&amp;room=3';res.end('<iframe title="Prueba de inserción" src="'+src+'" width="100%" height="1400"></iframe>');});
    await new Promise((resolve,reject)=>{embedServer.once('error',reject);embedServer.listen(0,'127.0.0.1',resolve);});
    await page.goto('http://127.0.0.1:'+embedServer.address().port+'/embed-test');
    await page.frameLocator('iframe').getByRole('button',{name:'Actualizar',exact:true}).waitFor();
    check(await page.frameLocator('iframe').locator('.occupation-column').count()===1,'public iframe loads across origins');
    await page.screenshot({path:'.test-data/public/embedded-mobile.png',fullPage:true});
    const messages=fs.readdirSync('private/storage/test-mail').map(n=>({n,t:fs.statSync('private/storage/test-mail/'+n).mtimeMs})).sort((a,b)=>b.t-a.t).map(x=>JSON.parse(fs.readFileSync('private/storage/test-mail/'+x.n,'utf8')));
    const account=messages.find(x=>x.email.startsWith('http.other.')&&x.kind==='verify');
    await page.goto(base+'?page=login');await page.locator('[name=email]').fill(account.email);await page.locator('[name=password]').fill('Contraseña ficticia larga');await page.getByRole('button',{name:'Iniciar sesión',exact:true}).click();
    const implicit=await page.locator('#public-link').inputValue();check(implicit===base+'ocupacion.php?view=week&room=0','implicit date omitted');
    await page.goto(base+'?page=agenda&view=month&room=3&day='+day);
    const link=await page.locator('#public-link').inputValue();const iframe=await page.locator('#public-iframe').inputValue();
    check(link===base+'ocupacion.php?view=month&room=3&day='+day,'explicit state shared');
    check(iframe.includes('title="Ocupación pública de salas del Ajuntament de Palma"')&&iframe.includes('src="'+link.replaceAll('&','&amp;')+'"'),'accessible iframe code');
    await page.evaluate(()=>Object.defineProperty(navigator,'clipboard',{configurable:true,value:{writeText:async value=>{window.testCopied=value;}}}));
    check(await page.locator('#share-title').innerText()==='Compartir Ocupación Salas','exact share title');
    check(await page.locator('.share-occupancy button:visible').count()===1&&await page.locator('#copy-manual').isHidden(),'one visible button without code fields');
    await page.locator('.share-occupancy').screenshot({path:'.test-data/public/share-closed-mobile.png'});
    const shareButton=page.getByRole('button',{name:'Compartir',exact:true});
    await shareButton.click();await page.getByRole('menuitem',{name:'Copiar URL',exact:true}).click();
    check(await page.evaluate(()=>window.testCopied)===link,'copy link');
    check(await page.locator('#share-menu').isHidden()&&await page.locator('#copy-status').innerText()==='URL copiada','URL confirmation and closed menu');
    await shareButton.click();await page.getByRole('menuitem',{name:'Copiar iframe',exact:true}).click();
    check(await page.evaluate(()=>window.testCopied)===iframe,'copy iframe');
    check(await page.locator('#share-menu').isHidden()&&await page.locator('#copy-status').innerText()==='Código iframe copiado','iframe confirmation and closed menu');
    await shareButton.focus();await page.keyboard.press('ArrowDown');
    check(await page.getByRole('menuitem',{name:'Copiar URL',exact:true}).evaluate(e=>e===document.activeElement),'keyboard opens on first item');
    await page.keyboard.press('ArrowDown');check(await page.getByRole('menuitem',{name:'Copiar iframe',exact:true}).evaluate(e=>e===document.activeElement),'keyboard moves to iframe');
    await page.keyboard.press('Home');await page.keyboard.press('End');await page.keyboard.press('Enter');
    check(await page.locator('#copy-status').innerText()==='Código iframe copiado'&&await shareButton.evaluate(e=>e===document.activeElement),'keyboard activates and restores focus');
    await shareButton.click();await page.keyboard.press('Escape');
    check(await page.locator('#share-menu').isHidden()&&await shareButton.evaluate(e=>e===document.activeElement),'Escape closes and restores focus');
    await shareButton.click();await page.locator('#share-title').click();
    check(await page.locator('#share-menu').isHidden(),'outside click closes');
    await shareButton.click();await page.keyboard.press('Tab');check(await page.locator('#share-menu').isHidden(),'Tab leaves menu');
    for(const width of [1280,390]){await page.setViewportSize({width,height:900});await shareButton.click();await page.locator('#share-menu').screenshot({path:'.test-data/public/share-menu-'+width+'.png'});await page.keyboard.press('Escape');check(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'share responsive '+width);}
    await page.evaluate(()=>Object.defineProperty(navigator,'clipboard',{configurable:true,value:{writeText:async()=>{throw Error('Denied');}}}));
    for(const kind of ['URL','iframe']){
      await shareButton.click();await page.getByRole('menuitem',{name:'Copiar '+kind,exact:true}).click();
      check(await page.locator('#copy-content').inputValue()===(kind==='URL'?link:iframe),'manual content '+kind);
      check(await page.locator('#copy-content').evaluate(e=>e===document.activeElement&&e.selectionEnd-e.selectionStart===e.value.length),'manual selected '+kind);
      check(await page.locator('#share-menu').isHidden()&&await page.locator('#copy-manual').isVisible(),'manual area shown after failure '+kind);
    }
    await page.locator('.share-occupancy').screenshot({path:'.test-data/public/share-manual-mobile.png'});
    check((await page.locator('#copy-manual').innerText()).includes('Ctrl+C'),'fallback instruction');
    await page.evaluate(()=>Object.defineProperty(navigator,'clipboard',{configurable:true,value:{writeText:async value=>{window.testCopied=value;}}}));
    await shareButton.click();await page.getByRole('menuitem',{name:'Copiar URL',exact:true}).click();check(await page.locator('#copy-manual').isHidden(),'next successful copy hides manual area');
    check(errors.length===0,'no JavaScript errors');
    const auth=await context.request.get(base+'?page=agenda');
    check(auth.headers()['content-security-policy'].includes("frame-ancestors 'none'")&&auth.headers()['x-frame-options']==='DENY','authenticated framing still denied');
    const frameErrors=[];page.on('console',m=>{if(m.type()==='error')frameErrors.push(m.text());});await page.goto('http://127.0.0.1:'+embedServer.address().port+'/protected');await page.waitForLoadState('networkidle');check(frameErrors.some(m=>m.includes('frame-ancestors')||m.includes('X-Frame-Options')),'authenticated iframe actually blocked');
    console.log('PUBLIC BROWSER: '+checks+' comprobaciones correctas; iframe, copia, filtros y móvil.');
  } finally {await browser.close();if(embedServer)await new Promise(resolve=>embedServer.close(resolve));}
})().catch(e=>{console.error(e);process.exit(1);});
