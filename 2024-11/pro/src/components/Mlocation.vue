<script lang="ts" setup>
import { RouterLink, RouterView } from 'vue-router';
import { store } from '../store'
</script>

<template>
    <div class="text-blue-300 underline">
        <a target="_blank" :href="'https://www.google.com/maps/search/?api=1&query=' + data.latitude + '%2C' + data.longitude">{{data.shortaddress}}</a>
    </div>
</template>

<script lang="ts">
export default {
    props: [
        'data'
    ],
    mounted() {
        //console.log(this.data);
    }
}
</script>
