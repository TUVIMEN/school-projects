<script setup lang="ts">
import NavBar from '../components/NavBar.vue'
import Seller from '../components/Seller.vue'
import { store } from '../store'
import { getCategories, getMakes, getSearch, getSellerSearch } from '../api'
//import { ref } from 'vue';

</script>

<template>
  <nav-bar />
  <main class="mt-8">
      <p v-if="errmsg.length > 0" class="text-[1.8em] text-red-500 text-center">
        {{errmsg}}
      </p>
      <div v-else class="space-y-4">
          <seller v-if="seller" :data="seller" full="not null" @changed="handlechanged" />
          <uoffer v-if="seller" label="Dodaj" :userid="seller.userid" :userperm="seller.userperm" @changed="handlechanged" />
          <div>
              <form v-on:submit.prevent="search" class="flex m-auto flex-col space-y-2 text-gray-800 text-center p-3 bg-red-800 w-2/5">
                  <input v-model="s_query" type="text" placeholder="Szukaj" class="w-full m-auto h-10 rounded-lg"/>
                  <div class="flex space-x-2 justify-center">
                      <select v-model="s_category" class="w-1/3 h-10 text-gray-800">
                          <option v-if="store.categories !== null" v-for="c in store.categories" :value="c.code">
                            {{c.name}}
                          </option>
                      </select>
                      <select v-model="s_make" class="w-1/3 h-10 text-gray-800">
                          <option v-if="store.makes !== null" v-for="m in store.makes" :value="m.value">
                            {{m.value}}
                          </option>
                      </select>
                      <button type="submit" class="w-1/5 bg-blue-500 m-0 text-[1.6em] text-white hover:bg-blue-700">&gt;</button>
                  </div>
              </form>
          </div>
          <span class="text-center block text-[1.6em] text-red-500">Results: {{results}}</span>
          <div class="offers grid grid-cols-3 space-y-6 space-x-2 p-10">
              <RouterLink v-if="offers.length > 0" :to="'/offer/'+o.id" v-for="o in offers">
                <div class="offer flex flex-col space-y-2 p-4 hover:bg-red-500">
                    <img :src="showphoto(o.photo)" />
                    <b class="text-center">{{o.title}}</b>
                    <div class="flex justify-center space-x-2 text-[0.7em]">
                        <time class="bg-purple-900 p-1 rounded-ml" :datatime="o.created">{{o.created}}</time>
                        <div class="bg-green-900 p-1" >{{o.price}} <span>{{o.currency_name}}</span></div>
                        <RouterLink class="bg-yellow-900 p-1" :to="'/seller/'+o.sellerid">{{o.name}}</RouterLink>
                    </div>
                </div>
            </RouterLink>
          </div>
      </div>
  </main>
</template>

<script lang="ts">
    //v-html="o.description"
export default {
  data() {
    return {
      results: 0,
      s_query: "",
      s_category: "",
      s_make: 0,
      page: 1,
      pages: null,
      pagesize: null,
      offers: [],
      seller: null,
      errmsg: ""
    }
  },
  methods: {
    handlechanged() {
        this.offers = [];
        this.getdata();
    },
    async handleScroll(e) {
        if (document.documentElement.scrollHeight-document.documentElement.scrollTop < (window.innerHeight*2)) {
            window.removeEventListener('scroll',this.handleScroll);
            if (this.page >= this.pages)
                return;
            this.$router.replace({query:{"page":++this.page}});
            await this.getdata();
            window.addEventListener('scroll',this.handleScroll);
        }
    },

    search() {
        return this.getdata(true);
    },

    handleError(e) {
        if (e.status === 422) {
            this.errmsg = e.response.data.error;
        } else
            this.errmsg = e.message;
    },
    showphoto(i: string|null): string {
        if (i === null)
            return "";
        if (i.slice(0,8) === "https://")
            return i;
        return '/.offer_photos/' + i;
    },

    getdata(changepath=false) {
        let res;
        let issearch = (this.s_query.length > 0 || this.s_category.length > 0 || this.s_make > 0);

        if (this.$route.params.sellerid) {
            let id = parseInt(this.$route.params.sellerid as string);
            if (isNaN(id) || id < 0) {
                this.errmsg = "Incorrect seller id";
                return;
            }

            res = getSellerSearch(id,this.page,this.s_query,this.s_category,this.s_make);
            if (issearch)
                this.$router.push({path:"/seller/"+this.$route.params.sellerid,query:{query:this.s_query,page:this.page,s_category:this.s_category,s_make:this.s_make}});
        } else {
            res = getSearch(this.page,this.s_query,this.s_category,this.s_make);
            if (issearch)
                this.$router.push({path:"/search",query:{query:this.s_query,page:this.page,s_category:this.s_category,s_make:this.s_make}});
        }
        res.then(r => {
            if (r.data.seller)
                this.seller = r.data.seller;
            this.pages = r.data.pages;
            this.pagesize = r.data.pagesize;
            this.results = r.data.results;
            this.offers = this.offers.concat(r.data.list);
        }).catch(e => this.handleError(e));
    }
  },
  mounted() {
    let q = this.$route.query;
    let p = parseInt(q.page as string);
    if (!isNaN(p))
        this.page = p;

    window.addEventListener('scroll',this.handleScroll);

    getCategories();
    getMakes();

    let m = parseInt(q.make as string);
    if (!isNaN(m) && m > 0)
        this.s_make = m;

    if (q.query as string)
        this.s_query = (q.query as string)
    if (q.category as string)
        this.s_category = (q.category as string);

    this.getdata();
  },
  unmounted() {
    window.removeEventListener('scroll',this.handleScroll);
  }
}
</script>
