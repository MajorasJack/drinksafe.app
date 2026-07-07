<script setup lang="ts">
// The Drink Safe shield mark (matches the favicon and OG image). Inlined as a
// component so it renders from the bundled, hashed JS and is never affected by
// the browser's aggressive favicon caching.
</script>

<template>
    <svg
        viewBox="0 0 64 64"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
    >
        <rect width="64" height="64" rx="16" class="fill-brand-teal" />
        <g
            transform="translate(8 8) scale(2)"
            stroke="#0b1220"
            stroke-width="2.3"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
            <path d="M9 12l2 2 4-4" />
        </g>
    </svg>
</template>
