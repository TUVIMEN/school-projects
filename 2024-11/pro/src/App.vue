<script setup lang="ts">
import { RouterLink, RouterView } from 'vue-router';
import axios from 'axios';
import { store } from './store'
import { getUserLog } from './api'

/*import NavBar from './components/NavBar.vue'
   <nav-bar />*/

//import HelloWorld from './components/HelloWorld.vue'
//<HelloWorld msg="You did it!" />

/*function getCookie(cname) {
    let name = cname + "=";
    let ca = document.cookie.split(';');
    for(let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}*/

</script>

<template>
  <RouterView :key="$route.path"/>
</template>

<style scoped>
</style>

<script lang="ts">

/*let ka;
axios.post('/nothing').then(re => ka = re).catch(err => {
    console.log('IIIIIIIIIIII');
    console.log(err);
});

console.log('UUUUUUUUUUUUUUUUUUUUU');
console.log(ka);*/

export default {
  mounted() {
    getUserLog();
  }
}
</script>
