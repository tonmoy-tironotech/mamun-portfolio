<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";

defineProps({ wide: Boolean });

const page = usePage();
const menuOpen = ref(false);

const menu = [
    { href: "/", label: "Home", component: "Home" },
    { href: "/education", label: "Education", component: "Education" },
    { href: "/experience", label: "Experience", component: "Experience" },
    { href: "/projects", label: "Projects", component: "Projects" },
    { href: "/publications", label: "Publications", component: "Publications" },
    {
        href: "/student-corner",
        label: "Student Corner",
        component: "Student",
    },
    { href: "/hobbies", label: "Hobbies", component: "Hobbies" },
    { href: "/contact", label: "Contact", component: "Contact" },
];

const parentPages = {
    ProjectDetail: "Projects",
    DocumentDetail: "Student",
    StudentProfile: "Student",
};
const currentComponent = computed(
    () => parentPages[page.component] ?? page.component,
);
const currentLabel = computed(
    () =>
        menu.find((item) => item.component === currentComponent.value)?.label ??
        "Account",
);
const isAccountPage = computed(() => page.component.startsWith("auth/"));
const accountHref = computed(() => {
    const user = page.props.auth.user;

    if (!user) {
        return "/login";
    }

    return user.is_admin ? "/admin" : "/student-corner";
});
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
                <span class="menu-toggle-icon" aria-hidden="true"
                    ><span></span><span></span><span></span
                ></span>
            </button>
            <ul id="menu-list">
                <li
                    v-for="item in menu"
                    :key="item.href"
                    :class="{
                        current_page_item: currentComponent === item.component,
                    }"
                >
                    <Link :href="$url(item.href)" @click="menuOpen = false">{{
                        item.label
                    }}</Link>
                </li>
                <li
                    class="menu-account"
                    :class="{ current_page_item: isAccountPage }"
                >
                    <Link
                        :href="$url(accountHref)"
                        aria-label="Account"
                        title="Account"
                        @click="menuOpen = false"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            width="20"
                            height="20"
                            fill="currentColor"
                            aria-hidden="true"
                        >
                            <path
                                d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-4.42 0-8 2.24-8 5v3h16v-3c0-2.76-3.58-5-8-5Z"
                            />
                        </svg>
                        <span class="menu-account-label">Account</span>
                    </Link>
                </li>
            </ul>
        </div>
    </div>
    <div id="header-wrapper">
        <div id="header">
            <div id="logo">
                <slot name="logo">
                    <h1>
                        <a href="#"><center>Nursadul Mamun</center></a>
                    </h1>
                    <p></p>
                    <center>Research Assistant</center>
                    <p>
                        <a href="https://crss.utdallas.edu/CILab/"
                            ><center>
                                Cochlear Implant Processing Laboratory
                            </center></a
                        >
                    </p>
                    <p>
                        <a href="https://www.utdallas.edu/"
                            ><center>
                                The University of Texas at Dallas
                            </center></a
                        >
                    </p>
                </slot>
            </div>
        </div>
    </div>
    <div id="wrapper">
        <div id="page">
            <div id="page-bgtop">
                <div id="page-bgbtm">
                    <div id="content" :class="{ wide }">
                        <slot />
                    </div>
                    <div v-if="$slots.sidebar" id="sidebar">
                        <slot name="sidebar" />
                    </div>
                    <div style="clear: both">&nbsp;</div>
                </div>
            </div>
        </div>
    </div>
    <div id="footer"><p>&copy; Nursadul Mamun</p></div>
</template>
