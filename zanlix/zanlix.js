const puppeteer = require('puppeteer');
const fs = require('fs');
(async () => {
    const email = process.env._LOGIN;
    const browser = await puppeteer.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    const page = await browser.newPage();
    await page.goto('https://zanlix.eu/', { waitUntil: 'domcontentloaded', timeout: 0 });
    await page.type('input[name="email"]', email);
    await page.click('button[type="submit"]');
    await page.waitForFunction(
        () =>
            document.querySelector('body').innerText.includes('balance'), { timeout: 60000 });

    // ambil teks balance
    const balance = await page.$eval(
        '.stat-item:first-child .ms-2',
        el => el.innerText.trim()
    );

    // ambil cookies
    const cookies = await page.cookies();
    const cookieString = cookies.map(c => `${c.name}=${c.value}`).join('; ');

    console.log('Captured cookies:');
    console.log(cookieString);

    fs.writeFileSync('cookie.txt', cookieString);
    console.log('saved to cookie.txt');

    await browser.close();
})();
