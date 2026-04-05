import { ref, computed } from 'vue'

export function useTimer() {
    const elapsed = ref(0)
    const isRunning = ref(false)
    let intervalId = null

    const hours = computed(() => Math.floor(elapsed.value / 3600))
    const minutes = computed(() => Math.floor((elapsed.value % 3600) / 60))
    const seconds = computed(() => elapsed.value % 60)

    const display = computed(() => {
        const h = String(hours.value).padStart(2, '0')
        const m = String(minutes.value).padStart(2, '0')
        const s = String(seconds.value).padStart(2, '0')
        return `${h}:${m}:${s}`
    })

    function start() {
        if (isRunning.value) return
        isRunning.value = true
        intervalId = setInterval(() => elapsed.value++, 1000)
    }

    function pause() {
        isRunning.value = false
        clearInterval(intervalId)
    }

    function stop() {
        pause()
        const total = elapsed.value
        elapsed.value = 0
        return total
    }

    return { elapsed, isRunning, hours, minutes, seconds, display, start, pause, stop }
}
