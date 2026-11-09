<script setup>
import { computed } from 'vue';
import TablePager from '../components/TablePager.vue';
import SiteLayout from '../layouts/SiteLayout.vue';

const props = defineProps({
    current: { type: Object, required: true },
    old: { type: Object, required: true },
});

const groups = computed(() => [
    { key: 'current', title: 'Current Students', members: props.current },
    { key: 'old', title: 'Old Students', members: props.old },
]);
</script>

<template>
    <SiteLayout wide>
        <div class="post members">
            <h2 class="title"><a href="#"><b>Members</b></a></h2>

            <section v-for="group in groups" :key="group.key" class="member-section">
                <h3 class="member-heading">
                    {{ group.title }}
                    <span class="member-count">{{ group.members.total }}</span>
                </h3>

                <ul v-if="group.members.data.length" class="member-grid">
                    <li v-for="member in group.members.data" :key="member.id" class="member-card">
                        <img
                            class="member-photo"
                            :src="$url(member.profile_photo ?? '/images/default_avatar.svg')"
                            :alt="member.profile_photo ? `Photo of ${member.name}` : ''"
                            width="170"
                            height="170"
                            loading="lazy"
                        />
                        <h4 class="member-name">{{ member.name }}</h4>
                        <p v-if="member.education_level" class="member-level">{{ member.education_level }}</p>
                        <p v-if="member.student_id" class="member-meta">ID: {{ member.student_id }}</p>
                        <p class="member-email">{{ member.email }}</p>
                    </li>
                </ul>
                <p v-else class="member-empty">No {{ group.key }} students yet.</p>

                <TablePager :pagination="group.members" />
            </section>
        </div>
    </SiteLayout>
</template>
