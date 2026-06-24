// Market Ticker Configuration
const marketData = [
    { symbol: 'S&P 500', price: '4,783.45', change: '+2.3%' },
    { symbol: 'NASDAQ', price: '15,123.78', change: '+1.8%' },
    { symbol: 'DJIA', price: '37,592.98', change: '+0.7%' },
    { symbol: 'AAPL', price: '182.63', change: '-0.5%' },
    { symbol: 'GOOGL', price: '141.80', change: '+1.2%' },
    { symbol: 'MSFT', price: '378.91', change: '+0.9%' },
    { symbol: 'TSLA', price: '248.42', change: '-2.1%' },
    { symbol: 'JPM', price: '173.05', change: '+0.3%' },
    { symbol: 'BTC', price: '43,251.00', change: '+3.5%' },
    { symbol: 'ETH', price: '2,315.42', change: '+2.1%' }
];

function updateTicker() {
    const tickerContent = document.getElementById('tickerContent');
    if (tickerContent) {
        let tickerHTML = '';
        marketData.forEach(item => {
            const changeClass = item.change.startsWith('+') ? 'positive' : 'negative';
            tickerHTML += `<span style="margin-right: 30px;">
                <strong>${item.symbol}</strong> ${item.price} 
                <span class="${changeClass}">${item.change}</span>
            </span> `;
        });
        tickerContent.innerHTML = tickerHTML + tickerHTML; // Duplicate for seamless loop
    }
}

// Idle timeout (60 seconds)
let idleTimeout;
let countdownTimer;

function resetIdleTimer() {
    clearTimeout(idleTimeout);
    clearInterval(countdownTimer);
    idleTimeout = setTimeout(logoutUser, 60000);
    
    // Update idle countdown if element exists
    const countdownElement = document.getElementById('idleCountdown');
    if (countdownElement) {
        let seconds = 60;
        countdownElement.textContent = `Session expires in: ${seconds}s`;
        
        countdownTimer = setInterval(() => {
            seconds--;
            countdownElement.textContent = `Session expires in: ${seconds}s`;
            if (seconds <= 0) {
                clearInterval(countdownTimer);
            }
        }, 1000);
    }
}

function logoutUser() {
    window.location.href = 'gateway/logout.php?timeout=1';
}

// Track user activity
document.addEventListener('mousemove', resetIdleTimer);
document.addEventListener('keypress', resetIdleTimer);
document.addEventListener('click', resetIdleTimer);

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    updateTicker();
    resetIdleTimer();
    
    // Refresh ticker data every 10 seconds (mock update)
    setInterval(() => {
        marketData.forEach(item => {
            const fluctuation = (Math.random() * 2 - 1).toFixed(2);
            const price = parseFloat(item.price.replace(',', ''));
            const newPrice = (price + parseFloat(fluctuation)).toFixed(2);
            item.price = newPrice.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            item.change = (fluctuation >= 0 ? '+' : '') + fluctuation + '%';
        });
        updateTicker();
    }, 10000);
});