import Vue from 'vue'
import Axios from 'axios'
import DateFormat from 'date-fns/format'
import VueWaypoint from 'vue-waypoint'
import InfiniteScroll from 'v-infinite-scroll'

import App from './App.vue'

Vue.use(InfiniteScroll)
Vue.use(VueWaypoint)

Vue.filter('formatTime', function (value, format) {
	return DateFormat(value, format)
})

Axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
Vue.prototype.$http = Axios

new Vue({
	el: '#signup',
	render: h => h(App)
})
