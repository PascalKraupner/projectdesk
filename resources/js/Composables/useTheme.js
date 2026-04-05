import { useColorMode } from '@vueuse/core'

export function useTheme() {
    const mode = useColorMode({
        storageKey: 'vueuse-color-scheme',
        emitAuto: true,
    })

    function setLight() { mode.value = 'light' }
    function setDark() { mode.value = 'dark' }
    function setSystem() { mode.value = 'auto' }

    return { mode, setLight, setDark, setSystem }
}
