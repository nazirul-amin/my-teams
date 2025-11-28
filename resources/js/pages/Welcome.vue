<script setup lang="ts">
import { dashboard, login } from '@/routes';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { animate, splitText, stagger } from 'animejs';
import gsap from 'gsap';
import { onMounted } from 'vue';

const { name, auth } = usePage().props;

const CAT_SELECTOR = '#cat';
const CAT_SHADOW_SELECTOR = '#catShadow';
const COIN_WAVES_SELECTOR = '#coinWaves';
const COIN_OBJECT_SELECTOR = '#coinObject';
const TONGUE_SELECTOR = '#tongueObject';
const COIN_RECT_SELECTOR = '#coinRect';
const COIN_SHADOW_SELECTOR = '#coinShadow';
const HEAD_OBJECT_SELECTOR = '#headObject';
const EAR_TOP_SELECTOR = '#earObjectT';
const EAR_TOP_CHILD_SELECTOR = '#earObjectTChild';
const EAR_BOTTOM_SELECTOR = '#earObjectB';
const EAR_BOTTOM_CHILD_SELECTOR = '#earObjectBChild';
const TAIL_SELECTOR = '#tailObject';
const LEG_TOP_SELECTOR = '#legObjectT';
const LEG_BOTTOM_SELECTOR = '#legObjectB';
const EYE_SELECTOR = '#eyeObject';

const S = 0.17;
const WS = 0.3;

function catAnimation() {
    let waveAnimDuration = 0;

    const settings = (
        transformOrigin?: string,
        ease?: string,
        duration?: number,
    ) => {
        return {
            repeat: -1,
            defaults: {
                transformOrigin: transformOrigin || '50% 50%',
                ease: ease || 'none',
                duration: duration ?? S,
            },
        } as const;
    };

    const coinWavesElement = document.querySelector(COIN_WAVES_SELECTOR);
    const coinWavesNodes = coinWavesElement
        ? Array.from(coinWavesElement.children)
        : [];

    gsap.set(LEG_TOP_SELECTOR, { x: 10, rotate: -20 });
    gsap.set(LEG_BOTTOM_SELECTOR, { y: -4 });

    const coinRectTl = gsap
        .timeline({ repeat: -1 })
        .to(COIN_RECT_SELECTOR, {
            duration: 0.4,
            rotate: '360_cw',
            transformOrigin: '50% 50%',
            ease: 'none',
        })
        .set(COIN_RECT_SELECTOR, { rotate: 0 });

    const coinEllipseTl = gsap
        .timeline({ repeat: -1, defaults: { ease: 'none', duration: WS } })
        .to(COIN_OBJECT_SELECTOR, { x: 5 })
        .yoyo(true);

    const coinShadowTl = gsap
        .timeline({
            repeat: -1,
            defaults: {
                transformOrigin: '50% 50%',
                ease: 'none',
                duration: WS,
            },
        })
        .to(COIN_SHADOW_SELECTOR, { x: 10 })
        .yoyo(true);

    const coinWavesArrTl = coinWavesNodes.map((node) => {
        waveAnimDuration += 0.1;
        return gsap
            .timeline({
                repeat: -1,
                defaults: {
                    ease: 'none',
                    duration: 0.05 + waveAnimDuration / 10,
                },
            })
            .to(node, { x: -1.2 })
            .yoyo(true);
    });

    const wavesXTl = gsap
        .timeline({ repeat: -1, defaults: { ease: 'none', duration: WS } })
        .to(COIN_WAVES_SELECTOR, { x: 5 })
        .yoyo(true);

    const coinMainTl = gsap
        .timeline()
        .add(coinRectTl, 0)
        .add(coinEllipseTl, 0)
        .add(coinShadowTl, 0)
        .add(coinWavesArrTl, 0)
        .add(wavesXTl, 0);

    const tongueTl = gsap
        .timeline({
            repeat: -1,
            defaults: { duration: S, ease: 'none' },
            delay: S,
        })
        .set(TONGUE_SELECTOR, { y: -4 })
        .to(TONGUE_SELECTOR, { rotate: 8 })
        .yoyo(true);

    const headTl = gsap
        .timeline(settings('50% 100%', 'none', S))
        .to(HEAD_OBJECT_SELECTOR, { y: -2, rotate: -2 })
        .yoyo(true);

    const earTopTl = gsap
        .timeline(settings('0% 200%', 'none', S))
        .to(EAR_TOP_SELECTOR, { x: -2, rotate: -3 })
        .yoyo(true);

    const earTopChildTl = gsap
        .timeline(settings('100% 0%', 'none', S))
        .to(EAR_TOP_CHILD_SELECTOR, { rotate: -6 })
        .yoyo(true);

    const earTopMainTl = gsap.timeline().add(earTopTl, 0).add(earTopChildTl, 0);

    const earBottomTl = gsap
        .timeline(settings('100% 100%', 'none', S * 2))
        .to(EAR_BOTTOM_SELECTOR, { x: -2, y: 2 })
        .yoyo(true);

    const earBottomChildTl = gsap
        .timeline(settings('100% 0%', 'none', S * 2))
        .to(EAR_BOTTOM_CHILD_SELECTOR, { rotate: -2 })
        .yoyo(true);

    const earBottomMainTl = gsap
        .timeline()
        .add(earBottomTl, 0)
        .add(earBottomChildTl, 0);

    const headObjectMainTl = gsap
        .timeline()
        .add(tongueTl, 0)
        .add(headTl, 0)
        .add(earTopMainTl, 0)
        .add(earBottomMainTl, 0);

    const tailTl = gsap
        .timeline(settings('100% 50%', 'none', S))
        .to(TAIL_SELECTOR, { rotate: 14 })
        .yoyo(true);

    const catRunYTl = gsap
        .timeline(settings('100% -50%', 'none', S))
        .to(CAT_SELECTOR, { y: 10 })
        .yoyo(true);

    const legTopTl = gsap
        .timeline(settings('0% -12%', 'none', S * 2))
        .to(LEG_TOP_SELECTOR, { rotate: 105 })
        .yoyo(true);

    const legBottomTl = gsap
        .timeline(settings('100% 0', 'none', S * 2))
        .to(LEG_BOTTOM_SELECTOR, { rotate: -110 })
        .yoyo(true);

    const catShadowTl = gsap
        .timeline(settings('50% 50%', 'none', S))
        .to(CAT_SHADOW_SELECTOR, { scaleX: 1.1 })
        .to(CAT_SHADOW_SELECTOR, { scaleX: 0.8 });

    const eyeTl = gsap
        .timeline({ repeat: -1, defaults: { ease: 'none', duration: S * 2 } })
        .to(EYE_SELECTOR, { scaleX: 1.1, scaleY: 1.1 })
        .yoyo(true);

    const catMainTl = gsap
        .timeline()
        .add(headObjectMainTl, 0)
        .add(tailTl, 0)
        .add(catRunYTl, 0)
        .add(legTopTl, 0)
        .add(legBottomTl, 0)
        .add(catShadowTl, 0)
        .add(eyeTl, 0);

    gsap.timeline().add(coinMainTl, 0).add(catMainTl, 0);
}

onMounted(() => {
    const { words } = splitText('#hero-title') as { words: HTMLElement[] };

    animate(words, {
        y: { from: '100%', to: '0%' },
        duration: 900,
        ease: 'out(3)',
        delay: stagger(200),
    });

    catAnimation();
});
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div
        class="flex min-h-screen flex-col items-center justify-center bg-[#FFF7F3] px-4 py-10 text-[#1b1b18] sm:px-8 sm:py-12 lg:px-12 lg:py-16"
    >
        <div
            class="flex w-full max-w-6xl items-center justify-center opacity-100 transition-opacity duration-750 starting:opacity-0"
        >
            <main
                class="relative flex w-full flex-col gap-10 overflow-hidden rounded-3xl p-6 sm:p-8 lg:flex-row lg:items-center lg:gap-0 lg:p-10"
            >
                <!-- Image section -->
                <section
                    class="relative mt-6 flex flex-1 justify-center lg:mt-0"
                >
                    <div
                        class="relative h-72 w-full rounded-3xl bg-linear-to-br from-[#C599B6] via-[#FAD0C4] to-[#FFF7F3] p-6 lg:h-96"
                    >
                        <div
                            class="flex h-full w-full items-center justify-center"
                        >
                            <svg
                                class="h-full w-full"
                                style="filter: saturate(0.8) brightness(1.05)"
                                viewBox="0 0 412 412"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <g id="catRun">
                                    <ellipse
                                        id="catShadow"
                                        cx="101.229"
                                        cy="341.5"
                                        rx="51"
                                        ry="3"
                                        fill="#E66279"
                                    />
                                    <g id="cat">
                                        <g id="bodyMainObject">
                                            <path
                                                id="legObjectB"
                                                d="M61.0668 290.5C67.0668 287 70.0668 281 70.0668 281L93.5 301.5C93.5 301.5 73.0668 317.5 61.0668 319.5C49.0668 321.5 44.5668 318.5 42.0668 315C39.5668 311.5 39.0668 303.5 42.0668 298.5C45.0668 293.5 55.0668 294 61.0668 290.5Z"
                                                fill="#DFDFDF"
                                            />
                                            <g id="bodyObject">
                                                <path
                                                    d="M81.2291 247C81.4283 243.882 82.2291 238.5 82.2291 238.5L124.729 240.5C124.729 240.5 128.729 278.5 124.729 288.5C120.729 298.5 106.789 307.103 92.7291 305.5C79.6097 304.004 68.7291 298 66.2291 284.5C63.7291 271 80.2028 263.069 81.2291 247Z"
                                                    fill="white"
                                                />
                                                <path
                                                    d="M117.229 274.5C117.729 265 126.362 264 126.362 264C126.628 271.534 126.585 279.271 125.738 284.5C125.738 284.5 116.729 284 117.229 274.5Z"
                                                    fill="#FFCC00"
                                                />
                                                <path
                                                    d="M92.729 305.5C79.6096 304.004 68.729 298 66.229 284.5C66.229 284.5 72.729 298.5 89.729 301.5C106.729 304.5 124.729 288.5 124.729 288.5C120.729 298.5 106.789 307.103 92.729 305.5Z"
                                                    fill="#EAEAEA"
                                                />
                                                <path
                                                    d="M81.2291 247C81.4282 243.882 82.2291 238.5 82.2291 238.5L124.729 240.5C124.729 240.5 125.138 244.386 125.564 250C125.564 250 110.74 251.076 101.229 251C93.2609 250.937 80.8491 250 80.8491 250C81.0335 249.031 81.1632 248.032 81.2291 247Z"
                                                    fill="#EAEAEA"
                                                />
                                            </g>
                                            <g id="legObjectT">
                                                <path
                                                    id="legForm"
                                                    d="M99.729 307.5C96.229 301.5 90.229 298.5 90.229 298.5L115.5 282.5C115.5 282.5 126.729 295.5 128.729 307.5C130.729 319.5 127.729 324 124.229 326.5C120.729 329 112.729 329.5 107.729 326.5C102.729 323.5 103.229 313.5 99.729 307.5Z"
                                                    fill="white"
                                                />
                                                <path
                                                    id="legShadow"
                                                    d="M99.729 307.5C96.229 301.5 90.229 298.5 90.229 298.5C90.229 298.5 99.729 302 103.229 307.5C106.729 313 104.729 315.5 110.729 321.5C116.729 327.5 127.163 323.5 127.163 323.5C126.317 324.79 125.296 325.738 124.229 326.5C120.729 329 112.729 329.5 107.729 326.5C102.729 323.5 103.229 313.5 99.729 307.5Z"
                                                    fill="#EAEAEA"
                                                />
                                            </g>
                                            <g id="tailObject">
                                                <path
                                                    d="M58.229 268.5C62.729 272.5 74.229 273.5 74.229 273.5L77.2289 286C77.2289 286 76.2289 287 74.229 287.5C72.229 288 58.2289 291 44.2289 282.5C30.2289 274 25.2289 255.5 32.7289 252.5C40.2289 249.5 53.729 264.5 58.229 268.5Z"
                                                    fill="white"
                                                />
                                                <path
                                                    d="M74.229 287.5C72.229 288 58.229 291 44.2289 282.5C36.67 277.911 31.7347 270.406 29.974 264C29.974 264 38.229 277 49.229 282C60.229 287 77.229 286 77.229 286C77.229 286 76.2289 287 74.229 287.5Z"
                                                    fill="#EAEAEA"
                                                />
                                                <path
                                                    d="M39.994 265.215C40.8522 262.814 44.0969 264.808 45.8718 263.047C47.4153 261.516 48.435 259.965 48.6746 259.587C49.6888 260.456 50.6733 261.341 51.6097 262.207C51.1099 263.054 49.1022 266.253 46.7741 267.223C44.0825 268.343 39.1357 267.616 39.994 265.215Z"
                                                    fill="#FF705E"
                                                />
                                                <path
                                                    d="M56.229 272C57.729 270 58.229 268.5 58.229 268.5C59.0945 269.269 60.1265 269.891 61.229 270.393C61.229 270.393 60.229 274.5 57.729 276C55.229 277.5 50.229 277.5 50.729 275C51.229 272.5 54.729 274 56.229 272Z"
                                                    fill="#FF705E"
                                                />
                                            </g>
                                            <g id="handObject">
                                                <path
                                                    d="M167.729 264.5C161.229 264.5 110.729 266 110.729 266V241C110.729 241 146.729 241 160.229 236C173.729 231 180.229 229 182.729 231C185.229 233 186.229 239.5 182.729 248.5C179.229 257.5 174.229 264.5 167.729 264.5Z"
                                                    fill="white"
                                                />
                                                <path
                                                    d="M167.729 264.5C161.229 264.5 110.729 266 110.729 266C110.729 266 119.229 263.5 147.229 262.5C175.229 261.5 182.729 248.5 182.729 248.5C179.229 257.5 174.229 264.5 167.729 264.5Z"
                                                    fill="#EAEAEA"
                                                />
                                                <path
                                                    d="M160.229 236C173.729 231 180.229 229 182.729 231C182.729 231 166.229 241 142.229 243C118.229 245 110.729 252.5 110.729 252.5V241C110.729 241 146.729 241 160.229 236Z"
                                                    fill="#EAEAEA"
                                                />
                                            </g>
                                        </g>
                                        <g id="headObject">
                                            <g id="earObjectB">
                                                <g id="earObjectBChild">
                                                    <path
                                                        d="M104.573 93.0994C113.618 80.0442 126.124 71.9151 126.124 71.9151C126.124 71.9151 131.019 77.5087 138.807 92.9958C146.595 108.483 150.92 119.044 150.92 119.044L90.229 121.652C90.229 121.652 95.5276 106.155 104.573 93.0994Z"
                                                        fill="#E8E8E8"
                                                    />
                                                    <path
                                                        d="M119.403 99.8314C121.079 93.9842 124.189 88.8046 124.189 88.8046C124.189 88.8046 126.494 94.9291 128.075 98.812C129.856 103.186 132.784 110.423 132.784 110.423C132.784 110.423 127.601 109.743 124.282 109.953C121.445 110.132 117.1 111.143 117.1 111.143C117.1 111.143 117.726 105.679 119.403 99.8314Z"
                                                        fill="#FF705E"
                                                    />
                                                    <path
                                                        opacity="0.1"
                                                        d="M119.403 99.8314C121.079 93.9842 124.189 88.8046 124.189 88.8046L126.574 85.555C126.574 85.555 123.245 93.9852 121.943 99.6192C120.975 103.809 120.194 110.491 120.194 110.491L117.1 111.143C117.1 111.143 117.726 105.679 119.403 99.8314Z"
                                                        fill="black"
                                                    />
                                                </g>
                                            </g>
                                            <path
                                                d="M109.729 244C99.729 244 40.229 240.5 27.229 202.5C14.229 164.5 60.229 96 109.729 93.5C123.79 92.7898 138.093 97.9299 151.229 106.605C154.624 108.847 157.229 108 161.729 112C166.229 116 166.742 118.866 169.399 121.5C186.189 138.143 199.141 159.776 204.054 179.5C204.054 179.5 189.729 186 170.729 187.5C151.729 189 133.729 182.5 131.229 184C128.729 185.5 125.229 188.5 124.229 198C123.229 207.5 124.729 215 128.229 221C131.729 227 137.729 233.596 146.729 236.048C155.729 238.5 169.229 236.048 169.229 236.048C145.229 245 115.494 244 109.729 244Z"
                                                fill="white"
                                            />
                                            <g id="mouthObject">
                                                <path
                                                    d="M169.229 236.048C187.376 230.207 203.188 220.075 205.729 202.5C206.743 195.485 206.086 187.656 204.054 179.5C204.054 179.5 189.729 186 170.729 187.5C151.729 189 133.729 182.5 131.229 184C128.729 185.5 125.229 188.5 124.229 198C123.229 207.5 124.729 215 128.229 221C131.729 227 137.729 233.596 146.729 236.048C155.729 238.5 169.229 236.048 169.229 236.048Z"
                                                    fill="#B61400"
                                                />
                                                <path
                                                    id="mouthTeфths"
                                                    d="M170.729 186.5C151.729 188 133.729 181.5 131.229 183C131.229 183 130.229 189.5 132.229 192.5C134.229 195.5 154.729 197.5 164.229 197.5C173.729 197.5 206.193 195.5 206.193 195.5C206.274 190.127 205.524 184.399 204.054 178.5C204.054 178.5 189.729 185 170.729 186.5Z"
                                                    fill="white"
                                                />
                                            </g>
                                            <g id="earObjectT">
                                                <g id="earObjectTChild">
                                                    <path
                                                        d="M87.229 90.5C94.729 76.5 106.229 67 106.229 67C106.229 67 111.729 72 121.229 86.5C130.729 101 136.229 111 136.229 111L76.229 120.5C76.229 120.5 79.729 104.5 87.229 90.5Z"
                                                        fill="white"
                                                    />
                                                    <path
                                                        d="M102.729 95.5C103.729 89.5 106.229 84 106.229 84C106.229 84 109.216 89.8223 111.229 93.5C113.496 97.6428 117.229 104.5 117.229 104.5C117.229 104.5 112.002 104.414 108.729 105C105.93 105.501 101.729 107 101.729 107C101.729 107 101.729 101.5 102.729 95.5Z"
                                                        fill="#FF705E"
                                                    />
                                                    <path
                                                        opacity="0.1"
                                                        d="M102.729 95.5C103.729 89.5 106.229 84 106.229 84L108.229 80.5C108.229 80.5 105.881 89.2544 105.229 95C104.744 99.2728 104.729 106 104.729 106L101.729 107C101.729 107 101.729 101.5 102.729 95.5Z"
                                                        fill="black"
                                                    />
                                                </g>
                                            </g>
                                            <path
                                                d="M27.229 202.5C40.229 240.5 99.729 244 109.729 244C115.494 244 145.229 245 169.229 236.048C169.229 236.048 155.729 238.5 146.729 236.048C146.729 236.048 114.729 238.952 90.729 235.5C66.7289 232.048 40.8905 222.228 29.7289 200.5C25.0915 191.472 26.629 174 26.629 174C24.3646 184.292 24.3672 194.135 27.229 202.5Z"
                                                fill="#EAEAEA"
                                            />
                                            <path
                                                d="M208.229 167C208.729 173 207.229 177 204.729 178C202.229 179 200.116 175.36 198.729 172.5C196.572 168.052 194.229 160 200.729 160C207.229 160 207.729 161 208.229 167Z"
                                                fill="black"
                                            />
                                            <g id="headEyebrowObject">
                                                <path
                                                    d="M150.729 132.5L145.229 128.5C145.229 128.5 145.348 127.786 145.673 126.713L150.96 130.793C150.767 131.811 150.729 132.5 150.729 132.5Z"
                                                    fill="black"
                                                />
                                                <path
                                                    d="M157.229 117C167.229 117.5 169.229 129 169.229 129C169.229 129 164.229 121.5 157.729 122.5C153.957 123.08 152.206 126.354 151.4 129L146.229 125.121C147.621 121.613 150.741 116.676 157.229 117Z"
                                                    fill="black"
                                                />
                                            </g>
                                            <g id="headSpotsObject">
                                                <path
                                                    d="M92.229 190C115.229 188 131.729 179 129.729 172.5C127.729 166 104.453 175.983 88.229 178C74.9618 179.65 55.729 174 54.229 180.5C52.729 187 69.229 192 92.229 190Z"
                                                    fill="#FF705E"
                                                />
                                                <path
                                                    d="M57.2289 151C60.7289 156.5 75.7289 144.5 96.2289 143C116.729 141.5 131.229 148.5 133.729 141.5C136.229 134.5 122.729 126 92.2289 129C61.7289 132 53.7289 145.5 57.2289 151Z"
                                                    fill="#FF705E"
                                                />
                                            </g>
                                            <g id="eyeObject">
                                                <circle
                                                    id="eyeForm"
                                                    cx="171.729"
                                                    cy="164"
                                                    r="18.5"
                                                    fill="#FFCC00"
                                                />
                                                <path
                                                    d="M155.229 164C155.229 180 171.729 182.5 171.729 182.5C161.512 182.5 153.229 174.217 153.229 164C153.229 153.783 161.512 145.5 171.729 145.5C171.729 145.5 155.229 148 155.229 164Z"
                                                    fill="#EDAF01"
                                                />
                                                <path
                                                    d="M174.255 170.289H171.044V173.5H169.213V170.289H167.229V168.444H169.213V166.845H167.229V165.012H169.213V156.5H174.36C175.855 156.5 177.041 156.963 177.916 157.889C178.791 158.816 179.229 160.069 179.229 161.649C179.229 163.307 178.807 164.587 177.964 165.49C177.127 166.386 175.935 166.837 174.389 166.845H171.044V168.444H174.255V170.289ZM171.044 165.012H174.36C175.35 165.012 176.104 164.728 176.622 164.159C177.14 163.591 177.398 162.762 177.398 161.672C177.398 160.684 177.13 159.886 176.593 159.279C176.056 158.664 175.331 158.353 174.418 158.345H171.044V165.012Z"
                                                    fill="white"
                                                />
                                            </g>
                                        </g>
                                    </g>
                                </g>
                            </svg>
                        </div>
                    </div>
                </section>

                <!-- Text section -->
                <section
                    class="relative z-10 flex-1 space-y-5 p-2 text-center lg:-ml-24 lg:bg-[#FFF7F3]/10 lg:text-left lg:backdrop-blur-sm"
                >
                    <h1
                        id="hero-title"
                        class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl lg:text-5xl"
                    >
                        Teams that work
                        <br />
                        <span class="relative inline-block">
                            <span class="relative z-10 text-[#6C5CE7]">
                                even better together.
                            </span>
                            <span
                                class="absolute inset-x-0 bottom-0 h-2 rounded bg-[#C9F0E0] opacity-60"
                            />
                        </span>
                    </h1>
                    <p
                        class="mx-auto max-w-lg text-sm leading-relaxed text-slate-600 sm:text-base"
                    >
                        Manage your companies, teams, and members effortlessly.
                    </p>
                    <div class="flex flex-col items-center gap-4 pt-4">
                        <Link
                            :href="auth.user ? dashboard() : login()"
                            class="inline-flex w-52 items-center justify-center rounded-full bg-orange-500 px-5 py-2.5 text-sm font-semibold text-white shadow-[0_12px_30px_-15px_rgba(108,92,231,0.7)] transition-transform duration-150 hover:scale-[1.03] hover:bg-orange-500/90"
                        >
                            {{ auth.user ? 'Go to dashboard' : 'Get Started' }}
                        </Link>
                    </div>
                </section>
            </main>
        </div>
    </div>
</template>
