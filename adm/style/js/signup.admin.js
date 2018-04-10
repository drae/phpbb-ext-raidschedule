import Vue from 'vue'
import moment from 'moment'
import Axios from 'axios'

Axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
Vue.prototype.$http = Axios

/**
 *
 */
const bus = new Vue()

/**
 *
 */
const Modal = Vue.extend({
	delimiters: ['[[', ']]'],
	template: '#modal-template',
	props: ['show', 'onClose'],
	ready: function () {
		document.addEventListener('keydown', (e) => {
			if (this.show && e.keyCode === 27) {
				this.onClose()
			}
		})
	},
	methods: {
		close: function () {
			this.onClose()
		}
	}
})

Vue.component('Modal', Modal)

/**
 *
 */
const Calendar = Vue.extend({
	delimiters: ['[[', ']]'],
	data: function () {
		return {
			month: moment().month() + 1,
			year: moment().year(),
			days: [],
			raids: []
		}
	},
	created: function () {
		bus.$on('evt:GetRaids', this.GetRaids)
		bus.$on('evt:UpdateDays', this.UpdateDays)
	},
	template: '#calendar-template',
	computed: {
		monthName () {
			return moment().month(parseInt(this.month) - 1).format('MMMM')
		}
	},
	methods: {
		UpdateDays: function () {
			const m = () => moment().year(this.year).month(this.month - 1).startOf('month')

			const daysInMonth = m().daysInMonth()
			const previousMonthDays = m().date(1).day()
			const offset = 0 - previousMonthDays
			const nextMonthDays = offset + (6 - m().date(daysInMonth).day())
			const total = daysInMonth + previousMonthDays + nextMonthDays

			var days = []

			for (let i = offset; i < total; i++) {
				var current = m().add(i, 'd')

				days.push({
					today: (current.format('DD/MM/YYYY') === moment(new Date()).format('DD/MM/YYYY')),
					otherMonth: (i < 0 || i > daysInMonth - 1),
					date: current,
					unix: current.unix(),
					weekday: current.format('dddd'),
					day: current.format('Do'),
					number: current.format('D'),
					month: current.format('MMMM'),
					year: current.format('YYYY')
				})
			}

			this.days = days
		},
		GetRaids: function () {
			this.$http.get(location.href.replace(/^.*#/, '') + '&action=fetch&month=' + this.month + '&year=' + this.year).then((r) => {
				var raids = []

				for (let day in r.data) {
					raids[day] = []

					for (let raid of r.data[day]) {
						raids[day].push({
							rid: raid.rid,
							cid: raid.cid,
							time: raid.time,
							start: moment.unix(raid.time - 0).format('HH:mm'),
							instance: {
								id: raid.instance.id,
								name: raid.instance.name,
								code: raid.instance.code,
								colour: raid.instance.colour
							},
							repeat: {
								type: raid.repeat.type,
								int: raid.repeat.int,
								days: raid.repeat.days,
								end: raid.repeat.end
							}
						})
					}
				}

				this.raids = raids
			}).catch((error) => {
				console.log('Error fetching data (GetRaids): ' + error)
			})
		},
		ChangeMonth: function (m) {
			switch (m) {
				case 'prev':
					if (this.month === 1) {
						this.month = 12
						this.year--
						break
					}
					this.month--
					break
				case 'this':
					this.month = moment().month() + 1
					this.year = moment().year()
					break
				case 'next':
					if (this.month === 12) {
						this.month = 1
						this.year++
						break
					}
					this.month++
					break
			}

			this.UpdateDays()
			this.GetRaids()
		},
		OpenEventModal: function (day, raid) {
			bus.$emit('event-modal:open', day, raid)
		},
		OpenConfirmModal: function (raid) {
			bus.$emit('confirm-modal:open', raid)
		}
	}
})

/**
 *
 */
const EventModal = Vue.extend({
	delimiters: ['[[', ']]'],
	template: '#event-modal-template',
	created: function () {
		bus.$on('event-modal:open', this.OpenModal)
	},
	data: function () {
		return {
			unix: 0,
			day: 0,
			date: '',
			rid: 0,
			cid: 0,
			start: '',
			instance: 0,
			repeatType: '',
			repeatInt: '',
			repeatDays: [],
			repeatEnd: '',
			error: '',
			show: false
		}
	},
	computed: {
		repeatTypeWord: function () {
			switch (this.repeatType) {
				case 'W':
					return 'Weeks'
				default:
					return ''
			}
		}
	},
	methods: {
		close: function () {
			this.show = false
		},

		submitEvent: function () {
			// Calculate unix time
			const [hour, minute] = this.start.split(':')
			this.time = this.unix + (hour * 3600) + (minute * 60)

			// Serialize the form data
			var formData = new FormData(document.getElementById('event-form'))
			formData.append('rid', this.rid - 0)
			formData.append('cid', this.cid - 0)
			formData.append('time', this.time - 0)
			if (this.repeatEnd) {
				const [year, month, day] = this.repeatEnd.split('-')
				formData.set('repeat_end', moment([year, month - 1, day, 23, 59, 0]).unix())
			}

			this.error = ''
			this.$http.post(location.href.replace(/^.*#/, '') + '&action=' + ((!this.rid) ? 'add' : 'edit'), formData).then((r) => {
				bus.$emit('evt:GetRaids')
				this.show = false
			}).catch((error) => {
				this.error = error
			})
		},
		OpenModal: function (unix, data) {
			this.unix = unix
			this.day = moment.unix(unix - 0).format('D')
			this.date = moment.unix(unix - 0).format('dddd, MMMM Do YYYY')

			this.recurse = 'this'
			this.rid = 0
			this.cid = 0
			this.start = ''
			this.instance = 0
			this.repeatType = ''
			this.repeatInt = 1
			this.repeatDays = []
			this.repeatEnd = ''

			if (data) {
				this.rid = data.rid
				this.cid = data.cid
				this.start = data.start
				this.instance = data.instance.id
				this.repeatType = data.repeat.type
				this.repeatInt = data.repeat.int ? data.repeat.int - 0 : ''
				this.repeatDays = data.repeat.days
				this.repeatEnd = data.repeat.end ? moment.unix(data.repeat.end).format('YYYY-MM-DD') : ''
			}

			this.show = true
		}
	}
})

/**
 *
 */
const DeleteModal = Vue.extend({
	delimiters: ['[[', ']]'],
	created: function () {
		bus.$on('confirm-modal:open', this.OpenModal)
	},
	data: function () {
		return {
			rid: 0,
			error: '',
			show: false
		}
	},
	template: '#delete-modal-template',
	methods: {
		close: function () {
			this.show = false
		},
		deleteEvent: function () {
			if (this.rid) {
				this.error = ''

				// Serialize the form data
				var formData = new FormData(document.getElementById('delete-form'))
				formData.append('rid', this.rid - 0)

				this.$http.post(location.href.replace(/^.*#/, '') + '&action=delete', formData).then((r) => {
					bus.$emit('evt:GetRaids')
					this.show = false
				}).catch((r) => {
					this.error = r.data.error
				})
			}
		},
		OpenModal: function (data) {
			if (data.rid) {
				this.recurse = 'this'
				this.rid = data.rid
				this.show = true
			}
		}
	}
})

/**
 *
 */
new Vue({
	delimiters: ['[[', ']]'],
	el: '#admin-calendar',
	mounted: function () {
		bus.$emit('evt:UpdateDays')
		bus.$emit('evt:GetRaids')
	},
	components: {
		Calendar,
		EventModal,
		DeleteModal
	},
	data: {
		showEventModal: false,
		showConfirmModal: false
	}
})
