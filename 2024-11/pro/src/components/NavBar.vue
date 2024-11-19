<script lang="ts" setup>
import { RouterLink, RouterView } from 'vue-router';
import { store } from '../store'
</script>

<template>
  <div class="wrapper text-red-500 h-[4em]">
      <nav class="w-full bg-grey-800 border-dashed border-red-500 border-b-2 text-center p-4 flex">
        <RouterLink to="/" class="decoration-2 text-2xl">
            MOTOLOGO
        </RouterLink>
        <div class="ml-auto text-ml">
            <div class="flex space-x-4" v-if="store.user === null">
                <RouterLink class="navbar-block" to="/login">Zaloguj się</RouterLink>
                <RouterLink class="navbar-block" to="/register">Zarejestruj się</RouterLink>
            </div>
            <div class="flex space-x-4" v-else>
                <RouterLink :to="'/user/'+store.user.id" class="flex flex-row">
                    <img v-if="store.user.avatar !== undefined && store.user.avatar !== null" :src="'/.avatars/'+store.user.avatar" class="i_img" />
                    <p class="text-blue-600 hover:text-blue-800 ml-auto text-ml">{{store.user.name}}</p>
                </RouterLink>
                <RouterLink class="navbar-block" to="/">Wiadomości</RouterLink>
                <div class="navbar-block cursor-pointer" v-on:click="logout">Wyloguj</div>
            </div>
        </div>
      </nav>
  </div>
</template>

<script lang="ts">
    export default {
        methods: {
            logout() {
                store.user = null;
                document.cookie = '';
            }
        }
    }
</script>

<style scoped>
    img.i_img {
        max-height: 3.2em;
    }
</style>
