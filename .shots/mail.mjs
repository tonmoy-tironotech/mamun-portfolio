import { createRequire } from 'node:module';

const puppeteer = createRequire('/tmp/e2e/')('puppeteer-core');
const browser = await puppeteer.launch({
    executablePath: '/usr/bin/chromium-browser',
    args: ['--no-sandbox', '--disable-gpu', '--disable-dev-shm-usage', '--host-resolver-rules=MAP localhost host.docker.internal'],
});
const MP = 'http://host.docker.internal:8202/api/v1';
const log = (k, v) => console.log(k.padEnd(24), v);
const wait = (ms) => new Promise((r) => setTimeout(r, ms));

async function waitForMail(to, subjectPart) {
    for (let i = 0; i < 40; i++) {
        const { messages } = await (await fetch(`${MP}/messages`)).json();
        const m = messages.find((x) => x.To.some((t) => t.Address === to) && x.Subject.includes(subjectPart));
        if (m) return (await fetch(`${MP}/message/${m.ID}`)).json();
        await wait(3000);
    }
    throw new Error(`no mail to ${to} with "${subjectPart}"`);
}

async function flow(B, tag, shots) {
    const base = new URL(B).pathname.replace(/\/$/, '');
    const problems = [];
    const ctx = await browser.createBrowserContext();
    const page = await ctx.newPage();
    page.setDefaultTimeout(90000);
    page.setDefaultNavigationTimeout(120000);
    await page.setViewport({ width: 1400, height: 1000 });
    page.on('dialog', (d) => d.accept());
    page.on('pageerror', (e) => problems.push('pageerror: ' + e.message));
    page.on('response', (r) => { if (r.status() >= 500 || r.status() === 419 || (r.status() === 404 && !r.url().includes('favicon'))) problems.push(r.status() + ' ' + r.url()); });
    console.log('== ' + B);

    // Contact form: saved, then mailed by the queue worker
    await page.goto(B + '/contact', { waitUntil: 'networkidle2' });
    await page.waitForSelector('.contact-form');
    await wait(300);
    if (shots) await page.screenshot({ path: '/out/contact.png', fullPage: true });
    const subject = `E2E Question ${tag}`;
    await page.type('#contact-name', 'E2E Visitor');
    await page.type('#contact-email', `e2e-visitor-${tag}@example.com`);
    await page.type('#contact-subject', subject);
    await page.type('#contact-message', 'First line <b>not bold</b>\nSecond line');
    const started = Date.now();
    await page.click('.contact-form .auth-submit');
    await page.waitForSelector('.contact-form .form-success');
    log('contact', (await page.$eval('.form-success', (e) => e.textContent)) + ` (${Date.now() - started}ms)`);
    log('form cleared', (await page.$eval('#contact-message', (e) => e.value)) === '');
    if (shots) await page.screenshot({ path: '/out/contact-sent.png', fullPage: true });

    const mail = await waitForMail('admin@mamun.com', subject);
    log('admin mail', mail.Subject + ' | reply-to ' + mail.ReplyTo.map((r) => r.Address).join());
    log('mail escaped', mail.HTML.includes('&lt;b&gt;not bold&lt;/b&gt;'));
    log('mail link', (mail.HTML.match(/href="([^"]+)"/) || [])[1]);

    // Admin reads it
    await page.goto(B + '/login', { waitUntil: 'networkidle2' });
    await page.waitForSelector('.auth-form');
    await page.type('#email', 'e2e-admin@example.com');
    await page.type('#password', 'brand-new-password-1');
    await page.click('.auth-submit');
    await page.waitForSelector('.admin-topbar h1');
    await wait(300);
    log('nav count', await page.$eval('.nav-count', (e) => e.textContent));
    await page.click(`.admin-nav a[href="${base}/admin/messages"]`);
    await page.waitForSelector('table');
    await wait(300);
    log('list', await page.$eval('tbody tr', (r) => r.innerText.replace(/\s+/g, ' ').slice(0, 90)));
    if (shots) await page.screenshot({ path: '/out/messages.png' });
    await page.type('.search', subject);
    await page.waitForFunction(() => document.querySelectorAll('tbody tr').length === 1 && !document.querySelector('.empty'));
    await page.click('.col-actions a');
    await page.waitForSelector('.message-body');
    await wait(300);
    log('message body', JSON.stringify(await page.$eval('.message-body', (e) => e.textContent)));
    log('count after read', await page.$$eval('.nav-count', (e) => e.length ? e[0].textContent : 'hidden'));
    // Reply from the panel, delivered by the queue
    await page.type('#reply-body', 'Thanks for writing.\nHere is the <i>answer</i>.');
    await page.click('form.card button[type=submit]');
    await page.waitForSelector('.reply-item');
    await page.waitForSelector('.alert');
    await wait(300);
    log('reply', await page.$eval('.alert', (e) => e.textContent) + ' | form cleared ' + ((await page.$eval('#reply-body', (e) => e.value)) === ''));
    log('thread', JSON.stringify(await page.$eval('.reply-item .message-body', (e) => e.textContent)));
    if (shots) await page.screenshot({ path: '/out/message.png', fullPage: true });
    const replyMail = await waitForMail(`e2e-visitor-${tag}@example.com`, `Re: ${subject}`);
    log('reply mail', replyMail.Subject + ' | reply-to ' + replyMail.ReplyTo.map((r) => r.Address).join() + ' | quotes original ' + replyMail.Text.includes('First line') + ' | escaped ' + replyMail.HTML.includes('&lt;i&gt;answer'));
    await page.goto(B + '/admin/messages', { waitUntil: 'networkidle2' });
    await page.waitForSelector('tbody tr');
    log('status', await page.$eval('tbody tr .badge', (e) => e.textContent.trim()));
    await page.click('tbody tr .col-actions a');
    await page.waitForSelector('.reply-item');
    await page.click('form.card .btn-danger');
    await page.waitForSelector('table');
    await page.waitForSelector('.alert');
    log('delete', await page.$eval('.alert', (e) => e.textContent));
    await page.click('.admin-user button');
    await page.waitForSelector('.auth-form');

    // Forgot password through the emailed link
    const email = `e2e-stu-${tag}@example.com`;
    await page.click('.auth-forgot a');
    await page.waitForSelector('.auth-hint');
    log('forgot page', await page.evaluate(() => location.pathname));
    if (shots) await page.screenshot({ path: '/out/forgot.png', clip: { x: 100, y: 330, width: 1200, height: 620 } });
    await page.type('#email', email);
    await page.click('.auth-submit');
    await page.waitForSelector('.form-success');
    log('forgot', await page.$eval('.form-success', (e) => e.textContent));

    const reset = await waitForMail(email, 'Reset your password');
    const link = (reset.Text.match(/https?:\/\/\S*reset-password\S+/) || [])[0];
    log('reset link', link.replace(/reset-password\/[^?]+/, 'reset-password/<token>'));

    // The worker's APP_URL is the root site, so the link is only opened on the root flow
    if (!base) {
        await page.goto(link, { waitUntil: 'networkidle2' });
        await page.waitForSelector('#password_confirmation');
        log('email prefilled', await page.$eval('#email', (e) => e.value));
        await page.type('#password', 'reset-password-99');
        await page.type('#password_confirmation', 'reset-password-99');
        await page.click('.auth-submit');
        await page.waitForSelector('.auth-forgot');
        await wait(300);
        log('after reset', (await page.evaluate(() => location.pathname)) + ' | ' + (await page.$eval('.form-success', (e) => e.textContent)));
        await page.type('#email', email);
        await page.type('#password', 'reset-password-99');
        await page.click('.auth-submit');
        await page.waitForSelector('.student');
        log('login new password', await page.evaluate(() => location.pathname));
        await page.click('.student-logout[type], button.student-logout');
        await page.waitForSelector('.auth-form');

        // A used link no longer works
        await page.goto(link, { waitUntil: 'networkidle2' });
        await page.waitForSelector('#password_confirmation');
        await page.type('#password', 'another-password-1');
        await page.type('#password_confirmation', 'another-password-1');
        await page.click('.auth-submit');
        await page.waitForSelector('.field-error');
        log('reused link', await page.$eval('.field-error', (e) => e.textContent));
    } else {
        const path = link.replace(/^https?:\/\/[^/]+/, '');
        await page.goto(B + path, { waitUntil: 'networkidle2' });
        await page.waitForSelector('#password_confirmation');
        await page.type('#password', 'reset-password-99');
        await page.type('#password_confirmation', 'reset-password-99');
        await page.click('.auth-submit');
        await page.waitForSelector('.auth-forgot');
        log('subfolder reset', await page.evaluate(() => location.pathname));
    }

    if (shots) {
        await page.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true });
        await page.goto(B + '/contact', { waitUntil: 'networkidle2' });
        await page.waitForSelector('.contact-form');
        await wait(300);
        log('phone overflow', await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth));
        await page.screenshot({ path: '/out/contact-m.png', fullPage: true });
    }
    log('problems', problems.length ? JSON.stringify(problems) : 'none');
    await ctx.close();
}

await flow('http://localhost:8200', 'root', true);
await flow('http://localhost:8300/mamun/public', 'sub', false);
await browser.close();
