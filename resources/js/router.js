import Vue from 'vue'
import Router from 'vue-router'
import Home from './pages/Home.vue'
import NotFound from './component/NotFound.vue'

Vue.use(Router)

export default new Router({
  mode: 'history',
  routes: [
    {
      path: '/',
      alias: '',
      name: 'Home',
      component: Home
    },
    {
      path: '*',
      name: "404",
      component: NotFound
    }
  ]
})
