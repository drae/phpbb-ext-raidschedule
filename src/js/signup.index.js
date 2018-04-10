import Vue from 'vue'
import Axios from 'axios'
import Moment from 'moment'
import { extendMoment } from 'moment-range'
import InfiniteLoading from 'vue-infinite-loading';
import Ps from 'perfect-scrollbar';

const moment = extendMoment(Moment);

/**
 *
 */
Vue.filter('formatTime', function (value, format) {
	return moment.unix(value).format(format)
})

Axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
Vue.prototype.$http = Axios

/**
 *
 */
const bus = new Vue()

/**
 *
 */
const SignupTimeline = Vue.extend({
	template: '#signup-timeline',
	delimiters: ['[[', ']]'],
	data: function () {
		return {
			start: 0,
			end: 0,
			entries: []
		}
	},
	created: function () {
		bus.$on('PlayerStatusChanged', this.PlayerStatusChanged)
		bus.$on('PlayerSigned', this.PlayerSigned)
	},
	mounted() {
		Ps.initialize(this.$refs.scrollWrapper)
	},
	props: ['rid', 'uid'],
	methods: {
		SelectRaid: function (rid) {
			bus.$emit('FetchRaid', rid)

			this.entries.forEach(function (day) {
				day.active = day.events.find(event => event.rid === rid) ? 1 : 0
			})
		},
		PlayerStatusChanged: function (rid, signed, selected) {
			this.entries.forEach(function (day) {
				if (day.events.find(event => event.rid === rid)) {
					day.signed = signed
					day.selected = selected
				}
			})
		},
		PlayerSigned: function (data) {
			this.entries.forEach(function (day) {
				if (day.events.find(event => event.rid === data.rid)) {
					day.signed = data.signed
					day.selected = data.selected
				}
			})
		},
		InfiniteHandler($state) {
			this.$http.get('/signup/timeline/' + this.end + '/next').then((r) => {
				this.start = r.data.start
				this.end = r.data.end
				this.events = r.data.events

				if (this.events.length)
				{
					const dateRange = moment.range(moment.unix(this.start).format('YYYY-MM-DD'), moment.unix(this.end).format('YYYY-MM-DD'))

					for (let day of dateRange.by('day')) {

						var events = this.events.filter(event => event.day === day.format('YYYY-MM-DD'))
						var info = events.find(event => event.type === 1)

						this.entries.push({
							active: events.find(event => event.rid === this.rid) ? 1 : 0,
							time: day.format('YYYY-MM-DD'),
							l_time: day.format('ddd Do MMM YYYY'),
							today: (day.format('YYYY-MM-DD') === moment().format('YYYY-MM-DD')) ? 1 : 0,
							type: info ? info.type : 0,
							signed: info ? info.signed : 0,
							selected: info ? info.selected : 0,
							colour: info ? info.colour : '',
							events: events
						})
					}

					$state.loaded()

					this.$nextTick(() => {
						Ps.update(this.$refs.scrollWrapper);
					});
				}
				else
				{
					$state.complete()
				}
			}).catch((r) => {

			})
		}
	},
	components: {
		InfiniteLoading
	}
})

/**
 *
 */
const PlayerListing = Vue.extend({
	delimiters: ['[[', ']]'],
	template: '#player-signup-list',
	data: function () {
		return players
	},
	props: ['rid', 'uid'],
	created: function () {
		bus.$on('FetchRaid', this.FetchRaid)
		bus.$on('PlayerSigned', this.PlayerSigned)
	},
	methods: {
		SelectPlayer: function (uid) {
			this.$http.put('/signup/' + this.rid + '/select/' + uid).then((r) => {
				this.total_raiders = r.data.total_raiders
				this.total_selected = r.data.total_selected
				this.total_signed = r.data.total_signed
				this.total_unsigned = r.data.total_unsigned

				this.roles.forEach(function (role) {
					role.players.forEach(function (player) {
						if (player.uid === r.data.uid) {
							player.selected = r.data.selected
							player.l_selected = r.data.l_selected

							bus.$emit('PlayerStatusChanged', this.rid, r.data.signed, r.data.selected)
						}
					})
				})

			}).catch((r) => {

			})
		},
		FetchRaid: function (rid) {
			this.roles.splice(0, this.roles.length)
			this.$http.get('/signup/' + rid).then((r) => {
				this.roles = r.data.players

				this.total_raiders = r.data.total_raiders
				this.total_selected = r.data.total_selected
				this.total_signed = r.data.total_signed
				this.total_unsigned = r.data.total_unsigned
				this.can_select = r.data.can_select

				bus.$emit('FetchRaidUpdated', r.data)
			}).catch((r) => {

			})
		},
		PlayerSigned: function (data) {
			this.total_raiders = data.total_raiders
			this.total_selected = data.total_selected
			this.total_signed = data.total_signed
			this.total_unsigned = data.total_unsigned

			this.roles.forEach(function (role) {
				role.players.forEach(function (player) {
					if (player.uid === data.uid) {
						player.signed = data.signed ? 1 : -1
						player.selected = data.selected
						player.l_selected = data.l_selected
					}
				})
			})
		}
	}
})

/**
 *
 */
new Vue({
	delimiters: ['[[', ']]'],
	el: '#signup',
	data: root,
	created: function () {
		bus.$on('FetchRaidUpdated', this.UpdateRaid)
	},
	computed: {
		raidBanner: function () {
			return {
				backgroundImage: "url('/ext/numeric/raidschedule/styles/aquila/images/" + this.raid_banner + "')"
			}
		}
	},
	methods: {
		UpdateRaid: function (data) {
			this.rid = data.rid
			this.uid = data.uid
			this.raid_name = data.raid_name
			this.raid_date = data.raid_date
			this.raid_banner = data.raid_banner
			this.raid_posts = data.raid_posts
			this.raid_topic = data.raid_topic
			this.player_signed = data.signed
			this.can_sign = data.can_sign
		},
		RaidSign: function () {
			this.$http.put('/signup/' + this.rid + '/sign').then((r) => {
				this.player_signed = r.data.signed

				bus.$emit('PlayerSigned', r.data)
			}).catch((r) => {

			})
		}
	},
	components: {
		PlayerListing,
		SignupTimeline,
	}
})
