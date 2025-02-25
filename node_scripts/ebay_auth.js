const puppeteer = require('puppeteer');

async function ebayAuth() {
  // Get command line arguments
  const authUrl = process.argv[2];
  const username = process.argv[3];
  const password = process.argv[4];
  const expectedState = process.argv[5];

  if (!authUrl || !username || !password || !expectedState) {
    console.error(JSON.stringify({
      error: 'Missing required arguments: authUrl, username, password, or state'
    }));
    process.exit(1);
  }

  let browser;
  try {
    // Launch the browser
    browser = await puppeteer.launch({
      headless: true,
      args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    const page = await browser.newPage();
    
    // Navigate to eBay auth page
    await page.goto(authUrl, { waitUntil: 'networkidle2' });
    
    // Wait for username field and fill it
    await page.waitForSelector('#userid');
    await page.type('#userid', username);
    
    // Click the "Continue" button
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle2' }),
      page.click('#signin-continue-btn')
    ]);
    
    // Wait for password field and fill it
    await page.waitForSelector('#pass');
    await page.type('#pass', password);
    
    // Click the "Sign In" button
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle2' }),
      page.click('#sgnBt')
    ]);
    
    // Handle "I agree" button if present (sometimes appears on sandbox)
    try {
      const agreeButton = await page.$('#agree-button');
      if (agreeButton) {
        await Promise.all([
          page.waitForNavigation({ waitUntil: 'networkidle2' }),
          page.click('#agree-button')
        ]);
      }
    } catch (e) {
      // Continue if no agree button
    }
    
    // Wait for redirect to callback URL
    await page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 30000 });
    
    // Get current URL which should be the callback URL with code
    const currentUrl = page.url();
    
    // Parse the URL to extract code and state parameters
    const url = new URL(currentUrl);
    const code = url.searchParams.get('code');
    const state = url.searchParams.get('state');
    
    if (!code) {
      console.error(JSON.stringify({
        error: 'Authorization code not found in callback URL'
      }));
      process.exit(1);
    }
    
    if (state !== expectedState) {
      console.error(JSON.stringify({
        error: 'State parameter mismatch'
      }));
      process.exit(1);
    }
    
    // Output the code as JSON
    console.log(JSON.stringify({ code, state }));
    
  } catch (error) {
    console.error(JSON.stringify({
      error: `Authentication failed: ${error.message}`
    }));
    process.exit(1);
  } finally {
    if (browser) {
      await browser.close();
    }
  }
}

ebayAuth();