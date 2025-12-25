/* dashboard page script */
document.addEventListener('DOMContentLoaded', () => {
    const divElement = document.getElementById('wrarning-box')
    const elements = document.getElementById('wraning-elements')
    
    if (divElement && elements) {
        if (elements.innerHTML.trim() === '') {
            divElement.style.display = 'none'
        } else {
            divElement.style.display = 'block'
        }
    }
})
