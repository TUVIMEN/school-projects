<script lang="ts" setup>
import { store } from '../store'
import { getCategories, getCurrencies, getDetails, getParameters, getEquipments, tryAddOffer, tryUpdateOffer } from '../api'
</script>

<template>
    <div v-if="store.user !== null && ((store.user.id as number) === (userid as number) || userperm === null || (userperm as number) < (store.user.perm as number))" class="m-auto p-2 bg-blue-500 hover:bg-blue-700 rounded-xl text-[1.3em] cursor-pointer" v-on:click="active=!active">
        {{label}}
    </div>
    <form v-if="active" v-on:submit.prevent="pushoffer" class="uoffer flex flex-col space-y-4 justify-center m-auto">
        <input v-model="off.title" type="text" placeholder="tytuł" class="bg-gray-500" required />
        <div class="space-x-3">
            <label for="price">Cena: </label>
            <input name="price" v-model="off.price" required type="number" step="any" class="bg-gray-500" />
            <select v-model="off.currencyid" required>
                <option v-for="c in store.currencies" :value="c.id">
                    {{c.name}}
                </option>
            </select>
        </div>
        <div class="space-x-3">
            <label for="category">Categoria: </label>
            <select name="category" v-model="off.categoryid" required>
                <option v-for="c in store.categories" :value="c.id">
                    {{c.name}}
                </option>
            </select>
        </div>
        <textarea v-model="off.description" placeholder="opis" class="bg-gray-500 text-white"></textarea>

        <form v-on:submit.prevent="pushdetail" class="p-2 space-y-2" style="border: 1px solid blue;">
            <div class="text-xl">Szczegóły:</div>
            <div>
                <div v-for="(d, index) in off.details" class="flex flex-row space-x-2 justify-center" style="border-bottom: 1px solid red;">
                    <div class="w-1/4">{{store.details[d.id-1].name}}</div>
                    <div class="w-1/4">{{d.value}}</div>
                    <div v-on:click="deldetail(index)" class="w-1/4 text-blue-500 hover:text-blue-800 cursor-pointer">X</div>
                </div>
            </div>
            <div class="space-x-2">
                <select v-model="t_detail_name" required>
                    <option v-for="d in store.details" :value="d.id">
                        {{d.name}}
                    </option>
                </select>
                <input v-model="t_detail_value" type="text" required />
                <button type="submit" class="hover:text-blue-800">Dodaj</button>
            </div>
        </form>

        <form v-on:submit.prevent="pushparameter" class="p-2 space-y-2" style="border: 1px solid blue;">
            <div class="text-xl">Parametry:</div>
            <div>
                <div v-for="(p, index) in off.parameters" class="flex flex-row space-x-2 justify-center" style="border-bottom: 1px solid red;">
                    <div class="w-1/4">{{store.parameters[p.id-1].name}}</div>
                    <div class="w-1/4">{{p.value}}</div>
                    <div v-on:click="delparameter(index)" class="w-1/4 text-blue-500 hover:text-blue-800 cursor-pointer">X</div>
                </div>
            </div>
            <div class="space-x-2">
                <select v-model="t_parameter_name" required>
                    <option v-for="p in store.parameters" :value="p.id">
                        {{p.name}}
                    </option>
                </select>
                <input v-model="t_parameter_value" type="text" required />
                <button type="submit" class="hover:text-blue-800">Dodaj</button>
            </div>
        </form>

        <form v-on:submit.prevent="pushequipment" class="p-2 space-y-2" style="border: 1px solid blue;">
            <div class="text-xl">Wyposażenie:</div>
            <div>
                <div v-for="(e, index) in off.equipments" class="flex flex-row space-x-2 justify-center" style="border-bottom: 1px solid red;">
                    <div v-if="store.equipments.length >= e" class="w-2/5">{{store.equipments[e-1].name}}</div>
                    <div v-on:click="delequipment(index)" class="w-2/5 text-blue-500 hover:text-blue-800 cursor-pointer">X</div>
                </div>
            </div>
            <div class="space-x-2">
                <select v-model="t_equipment_name" required>
                    <option v-for="e in store.equipments" :value="e.id">
                        {{e.name}}
                    </option>
                </select>
                <button type="submit" class="hover:text-blue-800">Dodaj</button>
            </div>
        </form>
        <form v-on:submit.prevent="pushphoto" class="p-2 space-y-2" style="border: 1px solid blue;">
            <div class="text-xl">Zdjęcia:</div>
            <div>
                <div v-for="(p, index) in off.photos" class="flex flex-row space-x-2 justify-center" style="border-bottom: 1px solid red;">
                    <img :src="showphoto(p)" />
                    <div v-on:click="delphoto(index)" class="w-2/5 text-blue-500 hover:text-blue-800 cursor-pointer">X</div>
                </div>
            </div>
            <div class="space-x-2">
                <input type="file" accept="image/png, image/jpeg" placeholder="logo" class="bg-gray-500" ref="fileInput" />
                <button type="submit" class="hover:text-blue-800">Dodaj</button>
            </div>
        </form>


        <button type="submit" class="p-2 bg-orange-500 hover:bg-orange-500">Zapisz</button>
    </form>
</template>

<script lang="ts">
    export default {
        data() {
            return {
                active: false,
                off: {
                    id: 0,
                    title: "",
                    price: 0,
                    currencyid: 0,
                    categoryid: 0,
                    sellerid: 0,
                    description: "",
                    isactive: true,
                    details: [],
                    parameters: [],
                    equipments: [],
                    photos: [],
                },
                photos: [],
                t_detail_name: 0,
                t_detail_value: "",
                t_parameter_name: 0,
                t_parameter_value: "",
                t_equipment_name: 0,
            }
        },
        props: [
            'data',
            'label',
            'userid',
            'userperm',
            'sellerid'
        ],
        methods: {
            showphoto(i: string|null): string {
                if (i === null)
                    return "";
                if (i.slice(0,8) === "https://")
                    return i;
                return '/.offer_photos/' + i;
            },
            pushdetail() {
                this.off.details = this.off.details.concat({
                    "id": this.t_detail_name,
                    "value": this.t_detail_value
                });
                this.t_detail_name = 0;
                this.t_detail_value = "";
            },
            deldetail(index: number) {
                this.off.details.splice(index,1);
            },
            pushparameter() {
                this.off.parameters = this.off.parameters.concat({
                    "id": this.t_parameter_name,
                    "value": this.t_parameter_value
                });
                this.t_parameter_name = 0;
                this.t_parameter_value = "";
            },
            delparameter(index: number) {
                this.off.parameters.splice(index,1);
            },
            pushequipment() {
                this.off.equipments = this.off.equipments.concat(
                    this.t_equipment_name
                );
                this.t_equipment_name = 0;
            },
            delequipment(index: number) {
                this.off.equipments.splice(index,1);
            },
            pushphoto() {
                let f = new FileReader();
                f.readAsDataURL((this.$refs.fileInput as any).files[0]);
                f.onload = () => this.off.photos = this.off.photos.concat(f.result as string);
            },
            delphoto(index: number) {
                this.off.photos.splice(index,1);
            },

            convoffer(d: any) {
                this.off.id = d.id;
                this.off.title = d.title;
                this.off.price = d.price;
                this.off.currencyid = d.currencyid;
                this.off.categoryid = d.categoryid;
                this.off.description = d.description;

                this.off.details = [];
                for (let de of d.details)
                    this.off.details = this.off.details.concat({"id":de.id,"value":de.value});

                this.off.parameters = [];
                for (let p of d.parameters)
                    this.off.parameters = this.off.parameters.concat({"id":p.id,"value":p.value});

                this.off.equipments = [];
                for (let e of d.equipments) {
                    for (let e2 of e[1])
                        this.off.equipments = this.off.equipments.concat(e2.id);
                }

                this.off.photos = [];
                for (let p of d.photos)
                    this.off.photos = this.off.photos.concat(p.src);
            },
            async pushoffer() {
                if (this.data === undefined) {
                    await tryAddOffer(this.off)
                } else
                    await tryUpdateOffer(this.off);
                this.active = false;
                this.$emit('changed');
            },
        },
        async mounted() {
            this.off.sellerid = this.sellerid;

            await getCategories();
            await getCurrencies();
            await getDetails();
            await getParameters();
            await getEquipments();

            if (this.data !== undefined)
                this.convoffer(this.data);
        }
    }
</script>

<style scoped>

.uoffer input, select {
    background-color: #333;
    color: white;
}

</style>
