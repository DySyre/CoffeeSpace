function loadImage(url) {
    return new Promise((resolve, reject) => {
        const image = new Image();
        image.onload = () => resolve(image);
        image.onerror = reject;
        image.src = url;
    });
}

export async function renderShareCard(qrUrl, logoSvg, name, kind, url) {
    const canvas = document.createElement('canvas');
    canvas.width = 720;
    canvas.height = 960;
    const ctx = canvas.getContext('2d');
    await document.fonts?.ready;
    ctx.fillStyle = '#f4e9da';
    ctx.fillRect(0, 0, 720, 960);
    ctx.fillStyle = '#4b2c20';
    ctx.fillRect(0, 0, 720, 174);
    if (logoSvg) {
        let svg = logoSvg.replace(/currentColor/g, '#fff7ec').replace(/\sclass="[^"]*"/g, '');
        if (!svg.includes('xmlns=')) svg = svg.replace('<svg', '<svg xmlns="http://www.w3.org/2000/svg"');
        svg = svg.replace('<svg', '<svg width="80" height="80"');
        const objectUrl = URL.createObjectURL(new Blob([svg], { type: 'image/svg+xml' }));
        try { ctx.drawImage(await loadImage(objectUrl), 50, 44, 80, 80); }
        finally { URL.revokeObjectURL(objectUrl); }
    }
    ctx.fillStyle = '#fff7ec';
    ctx.font = '600 42px "DM Sans", sans-serif';
    ctx.fillText('coffeeSPACE', 150, 104);
    ctx.fillStyle = '#4b2c20';
    ctx.textAlign = 'center';
    ctx.font = '600 28px "DM Sans", sans-serif';
    let headline = name;
    while (ctx.measureText(headline).width > 620 && headline.length) headline = headline.slice(0, -1);
    if (headline !== name) headline = headline.slice(0, -1) + '\u2026';
    ctx.fillText(headline, 360, 226);
    ctx.drawImage(await loadImage(qrUrl), 90, 260, 540, 540);
    ctx.font = '600 24px "DM Sans", sans-serif';
    ctx.fillText(kind === 'post' ? 'Scan to open this coffee story' : 'Scan to explore this coffee profile', 360, 856);
    ctx.font = '20px "DM Sans", sans-serif';
    ctx.fillStyle = '#795b48';
    ctx.fillText(new URL(url).host, 360, 899);
    return canvas.toDataURL('image/png');
}
