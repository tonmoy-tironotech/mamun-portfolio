<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const menuOpen = ref(false);

const menu = [
    { href: '/', label: 'Homepage', component: 'Home' },
    { href: '/education', label: 'Education', component: 'Education' },
    { href: '/experience', label: 'Experience', component: 'Experience' },
    { href: '/projects', label: 'Projects', component: 'Projects' },
    { href: '/publications', label: 'Publications', component: 'Publications' },
    { href: '/student-corner', label: 'Student Corner', component: 'StudentCorner' },
    { href: '/hobbies', label: 'Hobbies', component: 'Hobbies' },
    { href: '/contact', label: 'Contact', component: 'Contact' },
];

const currentLabel = computed(() => menu.find((item) => item.component === page.component)?.label);
</script>

<template>
    <div id="menu-wrapper">
        <div id="menu" :class="{ 'menu-open': menuOpen }">
            <button
                type="button"
                class="menu-toggle"
                aria-label="Toggle navigation"
                aria-controls="menu-list"
                :aria-expanded="menuOpen"
                @click="menuOpen = !menuOpen"
            >
                <span class="menu-toggle-label">{{ currentLabel }}</span>
                <span class="menu-toggle-icon" aria-hidden="true"><span></span><span></span><span></span></span>
            </button>
            <ul id="menu-list">
                <li
                    v-for="item in menu"
                    :key="item.href"
                    :class="{ current_page_item: page.component === item.component }"
                >
                    <Link :href="$url(item.href)" @click="menuOpen = false">{{ item.label }}</Link>
                </li>
            </ul>
        </div>
    </div>
    <div id="header-wrapper">
        <div id="header">
            <div id="logo">
                <slot name="logo">
                    <h1><a href="#"><center>Nursadul Mamun</center></a></h1>
                    <p></p><center>Research Assistant</center>
                    <p> <a href="https://crss.utdallas.edu/CILab/"><center>Cochlear Implant Processing Laboratory</center></a></p>
                    <p> <a href="https://www.utdallas.edu/"><center>The University of Texas at Dallas</center></a></p>
                </slot>
            </div>
        </div>
    </div>
    <div id="wrapper">
        <div id="page">
            <div id="page-bgtop">
                <div id="page-bgbtm">
                    <div id="content">
                        <slot />
                    </div>
                    <div v-if="$slots.sidebar" id="sidebar">
                        <slot name="sidebar" />
                    </div>
                    <div style="clear: both;">&nbsp;</div>
                </div>
            </div>
        </div>
    </div>
    <div id="footer"><p>&copy; Nursadul Mamun</p></div>
</template>
