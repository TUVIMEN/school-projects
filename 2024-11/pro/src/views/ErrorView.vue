<script setup lang="ts">
//import TheWelcome from '../components/TheWelcome.vue'
//<TheWelcome />
</script>

<template>
  <h1 className="hover:bg-red-500">loop</h1>
  <main>
      <h1>Error</h1>
      <p v-if="error">
        I was here
      </p>
  </main>
</template>

<script lang="ts">
export default {
  data() {
    return {
      user: null,
      name: 'Apples',
      message: 'I like apples',
      error: true,
      clicks: 0
    }
  },
  methods: {
    countClicks() {
      this.clicks++;
    }
  }
}
</script>
