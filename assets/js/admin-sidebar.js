/* admin sidebar script */
document.addEventListener('DOMContentLoaded', function () {
    const body = document.body
    const hamburger = document.getElementById("menu-button")
    const defaultState = body.dataset.sidebarDefault || "closed"

    if (!hamburger) {
        return
    }

    const toggleSidebar = () => {
        body.classList.toggle("active")
    }

    const syncSidebar = () => {
        hamburger.removeEventListener("click", toggleSidebar)
        hamburger.addEventListener("click", toggleSidebar)

        if (window.innerWidth <= 1024) {
            body.classList.add("active")
            return
        }

        if (defaultState === "open") {
            body.classList.remove("active")
        } else {
            body.classList.add("active")
        }
    }

    syncSidebar()
    window.addEventListener("resize", syncSidebar)
})
