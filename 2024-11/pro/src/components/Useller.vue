<script lang="ts" setup>
import { store } from '../store'
import { getServices, tryAddSeller, tryUpdateSeller } from '../api'
import { weekday } from '../o'
</script>

<template>
    <div v-if="store.user !== null && (store.user.id === userid || userperm === null || (userperm as number) < (store.user.perm as number))" class="m-auto p-2 bg-blue-500 hover:bg-blue-700 rounded-xl text-[1.3em] cursor-pointer" v-on:click="active=!active">
        {{label}}
    </div>
    <form v-if="active" v-on:submit.prevent="pushseller" class="useller flex flex-col space-y-4 justify-center m-auto">
        <input v-model="sel.name" type="text" placeholder="nazwa" class="bg-gray-500" required />
        <input v-model="sel.phonenumber" type="text" placeholder="numer telefonu" class="bg-gray-500" required />
        <input v-model="sel.website" type="text" placeholder="strona" class="bg-gray-500" />
        <div class="space-x-2">
            <label for="isprivate">Prywatny:</label>
            <input name="isprivate" v-model="sel.isprivate" type="checkbox" checked />
        </div>


        <input v-model="sel.location.city" required type="text" placeholder="miasto" class="bg-gray-500" />
        <input v-model="sel.location.region" type="text" class="bg-gray-500" placeholder="region" />
        <input v-model="sel.location.country" required type="text" class="bg-gray-500" placeholder="państwo" />
        <input v-model="sel.location.postalcode" required type="text" class="bg-gray-500" placeholder="kod pocztowy" />
        <input v-model="sel.location.address" required type="text" class="bg-gray-500" placeholder="adres" />
        <div class="space-x-3">
            <label for="latitude">Szerokość geograficzna: </label>
            <input name="latitude" v-model="sel.location.latitude" required type="number" step="any" class="bg-gray-500" />
        </div>
        <div class="space-x-3">
            <label for="longitude">Długość geograficzna: </label>
            <input name="longitude" v-model="sel.location.longitude" required type="number" step="any" class="bg-gray-500" />
        </div>

        <select v-model="sel.services" multiple>
            <option v-if="store.services" v-for="s in store.services" :value="s.id">
                {{s.name}}
            </option>
        </select>

        <div v-if="sel.workinghours">
            <div v-for="w in sel.workinghours" class="flex flex-row space-x-5 m-auto" >
                <div class="w-[5em]">
                    {{weekday(w.day)}}
                </div>
                <div class="space-x-4">
                    <span class="space-x-4">
                        <input type="number" min="0" max="24" v-model="w.openhour" />
                        :
                        <input type="number" min="0" max="60" v-model="w.openminute" />
                    </span>
                    <span>-&gt;</span>
                    <span class="space-x-4">
                        <input type="number" min="0" max="24" v-model="w.closehour" />
                        :
                        <input type="number" min="0" max="60" v-model="w.closeminute" />
                    </span>
                </div>
            </div>
        </div>

        <input type="file" accept="image/png, image/jpeg" placeholder="logo" class="bg-gray-500" v-on:change="changelogo" ref="fileInput" />

        <button type="submit" class="p-2 bg-orange-500 hover:bg-orange-500">Zapisz</button>
    </form>
</template>

<script lang="ts">
    export default {
        data() {
            return {
                active: false,
                sel: {
                    id: 0,
                    name: "",
                    phonenumber: "",
                    website: "",
                    isprivate: true,
                    logo: {
                        src: "",
                        alt: "n",
                        type: "n"
                    },
                    workinghours: [
                      {
                        "day": "1",
                        "openhour": "8",
                        "openminute": "0",
                        "closehour": "17",
                        "closeminute": "0"
                      },
                      {
                        "day": "2",
                        "openhour": "8",
                        "openminute": "0",
                        "closehour": "17",
                        "closeminute": "0"
                      },
                      {
                        "day": "3",
                        "openhour": "8",
                        "openminute": "0",
                        "closehour": "17",
                        "closeminute": "0"
                      },
                      {
                        "day": "4",
                        "openhour": "8",
                        "openminute": "0",
                        "closehour": "17",
                        "closeminute": "0"
                      },
                      {
                        "day": "5",
                        "openhour": "8",
                        "openminute": "0",
                        "closehour": "17",
                        "closeminute": "0"
                      },
                      {
                        "day": "6",
                        "openhour": "8",
                        "openminute": "0",
                        "closehour": "15",
                        "closeminute": "0"
                      },
                      {
                        "day": "0",
                        "openhour": "0",
                        "openminute": "0",
                        "closehour": "0",
                        "closeminute": "0"
                      }
                    ],
                    services: [],
                    location: {
                        city: "",
                        region: "",
                        country: "Polska",
                        postalcode: "",
                        address: "",
                        shortaddress: "",
                        latitude: 0,
                        longitude: 0,
                        zoom: 12,
                        radius: 1500,
                        c_city: "==",
                        c_region: "==",
                        c_subregion: "==",
                    }
                }
            }
        },
        props: [
            'data',
            'label',
            'userid',
            'userperm'
        ],
        methods: {
            convseller(d: any) {
                this.sel.id = d.id;
                this.sel.name = d.name;
                this.sel.website = d.website;
                this.sel.phonenumber = d.phonenumber;
                this.sel.isprivate = d.isprivate;

                if (d.logo.length > 0)
                    this.sel.logo.src = d.logo[0].src;

                this.sel.location = d.location;

                this.sel.workinghours = [];
                for (let w of d.workinghours) {
                    this.sel.workinghours = this.sel.workinghours.concat({
                        day: w.day,
                        openhour: w.openhour,
                        openminute: w.openminute,
                        closehour: w.closehour,
                        closeminute: w.closeminute
                    })
                }

                this.sel.services = [];
                for (let s of d.services) {
                    this.sel.services = this.sel.services.concat(s.id);
                }
            },
            async pushseller() {
                this.sel.location.shortaddress = this.sel.location.address
                    + " - " + this.sel.location.postalcode
                    + " " + this.sel.location.city
                    + " (" + this.sel.location.country
                    + ")";
                if (this.data === undefined) {
                    await tryAddSeller(this.sel)
                } else
                    await tryUpdateSeller(this.sel);
                this.active = false;
                this.$emit('changed');
            },
            changelogo() {
                let f = new FileReader();
                f.readAsDataURL((this.$refs.fileInput as any).files[0]);
                f.onload = () => this.sel.logo.src = (f.result as string);
            }
        },
        mounted() {
            getServices();
            if (this.data !== undefined)
                this.convseller(this.data);
        }
    }
</script>

<style scoped>

.useller input, select {
    background-color: #333;
    color: white;
}

</style>
