import Vue from 'vue'
import Vuex from 'vuex'
import axios from 'axios'
Vue.use(Vuex)
var Axios = axios.create({
    // baseURL: 'http://127.0.0.1:8000',
    baseURL: 'http://tradestockprofile.herokuapp.com',
    // baseURL: 'http://trade.stockprofile.info',
    headers: {
        'Content-Type': 'application/json'
    }
});

const store = new Vuex.Store({
    state: {
        q: []
    },
    mutations: {
        UPDATE_QUESTIONS_HANDLE(state, payload) {
            state.q = payload
        }
    },
    actions: {
        GET_STOCK({state, commit}, payload) {
            return new Promise((resolve, reject) => {
                Axios.get('/stock', {params:payload}).then(res => {
                    resolve(res.data)
                }, err => {
                    reject(err)
                });
            })
        },
    }
})
export default store;