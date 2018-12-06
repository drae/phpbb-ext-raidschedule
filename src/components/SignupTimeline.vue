<script>
	import InfiniteLoading from 'vue-infinite-loading'
	import Ps from 'perfect-scrollbar'
	import DateEachDay from 'date-fns/each_day'
	import DateFormat from 'date-fns/format'
	import isToday from 'date-fns/is_today'
	import { EventBus } from './../EventBus'

	export default {
		name: 'signupTimeline',
		data: function () {
			return {
				start: 0,
				end: 0,
				entries: []
			}
		},
		created: function () {
			EventBus.$on('PlayerStatusChanged', this.PlayerStatusChanged)
			EventBus.$on('PlayerSigned', this.PlayerSigned)
		},
		mounted() {
			Ps.initialize(this.$refs.scrollWrapper)
		},
		props: ['rid'],
		methods: {
			SelectRaid: function (rid) {
				EventBus.$emit('FetchRaid', rid)

				this.entries.forEach((day) => {
					day.active = day.events.find(event => event.rid === rid) ? 1 : 0
				})
			},
			PlayerStatusChanged: function (rid, signed, selected) {
				this.entries.forEach((day) => {
					if (day.events.find(event => event.rid === rid)) {
						day.signed = signed
						day.selected = selected
					}
				})
			},
			PlayerSigned: function (data) {
				this.entries.forEach((day) => {
					if (day.events.find(event => event.rid === data.rid)) {
						day.signed = data.signed
						day.selected = data.selected
					}
				})
			},
			InfiniteHandler($state) {
				this.$http.get('/signup/timeline/' + this.end + '/next').then((r) => {
					({ start: this.start, end: this.end, events: this.events} = r.data)

					if (this.events.length)
					{
						const dateRange = DateEachDay(new Date(this.start * 1000), new Date(this.end * 1000))

						for (let day of dateRange) {
							var events = this.events.filter(event => event.day === DateFormat(day, 'YYYY-MM-DD'))
							var info = events.find(event => event.type === 1)

							this.entries.push({
								active: events.find(event => event.rid === this.rid) ? 1 : 0,
								time: DateFormat(day, 'YYYY-MM-DD'),
								l_time: DateFormat(day, 'ddd Do MMM YYYY'),
								today: isToday(day) ? 1 : 0,
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
	}
</script>

<template>
	<div class="signup-timeline">
		<div id="perfect-scroll-wrapper" ref="scrollWrapper" infinite-wrapper class="timeline-container timeline">
			<li v-for="day in entries" :key="day.time" class="day">
				<div class="timeline-badge" :class="{'event': day.type, 'misc': !day.type, 'active': day.active, 'selected': day.selected == 1, 'reserve': day.selected == 2, 'signed': day.signed}" :style="{backgroundColor: day.colour}"></div>
				<div class="timeline-panel" :class="{'event' : day.type, 'misc': !day.type, 'active': day.active, 'selected': day.selected == 1, 'reserve': day.selected == 2, 'signed': day.signed}" :style="{ background: day.colour }">
					<span class="raid-date-day">{{ day.l_time }}</span>

					<template v-for="event in day.events">
						<span class="raid-date" :key="event.rid">
							<i class="fa fa-clock-o"></i>{{ event.time | formatTime('HH:mm') }}
						</span>
						<span v-if="event.link" :key="event.rid" class="raid-title"><a :href="event.link" @click.stop.prevent="SelectRaid(event.rid)">{{ event.title }}</a></span>
						<span v-else  :key="event.rid" class="raid-title">{{ event.title }}</span>
					</template>
				</div>
			</li>
			<infinite-loading @infinite="InfiniteHandler"></infinite-loading>
		</div>
	</div>
</template>

<style lang="scss" scoped>
	// Core variables and mixins
	@import "~bootstrap/scss/functions";
	@import "~bootstrap/scss/variables";
	@import "~bootstrap/scss/mixins/grid";
	@import "~bootstrap/scss/mixins/breakpoints";

	.signup-timeline {
		flex: 0 0 100%;
		max-height: 400px;
		margin: 20px 0 0;
		padding: 0;
		position: relative;

		@include media-breakpoint-up(md) {
			@include make-col(4);
			max-height: initial;
			padding-left: 20px;
		}

		&:before {
			background: rgba(#666, 0.5);
			bottom: 0;
			content: "";
			display: block;
			margin: 0 20px;
			position: absolute;
			top: 0;
			width: 1px;
		}

		.timeline-container {
			overflow: hidden;
			height: 600px;
		}

		.timeline {
			margin-bottom: 0;
			position: relative;

			li {
				padding-left: 0;
				list-style: none;
				margin-bottom: 10px;
				position: relative;

				&:last-child {
					margin-bottom: 0;
				}

				&.month {
					background-color: rgba(100, 100, 100, 1);
					margin-left: 10px;
					padding: 2px 10px 2px 35px;
				}

				.timeline-badge {
					background-color: rgba(100, 100, 100, 1);
					border-radius: 50%;
					color: #fff;
					font-size: 1.4em;
					left: 9px;
					line-height: 24px;
					position: absolute;
					text-align: center;
					top: 8px;

					&.event {
						height: 24px;
						width: 24px;
					}

					&.active {
						border: solid 3px #fff;
					}

					&.selected {
						box-shadow: 0 0 10px 5px rgba(0, 200, 0, 0.75);
					}

					&.reserve {
						box-shadow: 0 0 10px 5px rgba(20, 100, 200, 0.75);
					}

					&.signed {
						box-shadow: 0 0 10px 5px rgba(255, 255, 255, 0.75);
					}

					&.misc {
						height: 10px;
						left: 16px;
						top: 10px;
						width: 10px;
					}
				}

				.timeline-panel {
					margin-left: 44px;
					padding: 5px 10px;
					position: relative;

					&.event {
						background-color: rgba(150, 150, 150, 1);
					}

					&.misc {
						background-color: rgba(100, 100, 100, 1);
					}
				}

				span {
					display: block;
				}

				.raid-date {
					font-size: 0.9em;
				}

				.raid-title {
					font-size: 1.1em;
					padding-bottom: 5px;
					position: relative;

					&:last-of-type {
						padding-bottom: 0;
					}

					a {
						text-decoration: none;
					}
				}
			}
		}

		.post-list {
			margin-top: 5px;
			padding: 0;

			li {
				font-size: 1.1em;
			}

			a {
				padding: 8px 15px;

				&:hover {
					text-decoration: none;
					border-bottom: 0;
				}
			}
		}
	}
</style>
