/* page loading component */
document.addEventListener("DOMContentLoaded", function () {
    // hide loading component after page load
    document.getElementById("loader-wrapper").style.display = "none"
})

/* show loading component on link click */
document.addEventListener("DOMContentLoaded", function () {
    const loader = document.getElementById("loader-wrapper")
    document.body.addEventListener("click", function (event) {
        const target = event.target.closest("a")
        if (target && target.href) {
            event.preventDefault()
            loader.style.display = "flex"
            setTimeout(() => {
                window.location.href = target.href
            }, 10)
        }
    })
})

/* disable page loading for back/forward navigation */
window.addEventListener("pageshow", function (event) {
    if (event.persisted) { // check if page was loaded from cache
        const loader = document.getElementById("loader-wrapper")
        loader.style.display = "none" // hide loader when page is shown
    }
})
