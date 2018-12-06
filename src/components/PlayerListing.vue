<script>
	import { EventBus } from './../EventBus'

	export default {
		name: 'playerListing',
		data: function () {
			return { ...players, messages }
		},
		props: ['rid'],
		created: function () {
			EventBus.$on('FetchRaid', this.FetchRaid)
			EventBus.$on('PlayerSigned', this.PlayerSigned)
		},
		methods: {
			SelectPlayer: function (uid) {
				this.$http.put('/signup/' + this.rid + '/select/' + uid).then((r) => {
					({ total_raiders: this.total_raiders, total_selected: this.total_selected, total_signed: this.total_signed, total_unsigned: this.total_unsigned } = r.data)

					this.roles.forEach(function (role) {
						role.players.forEach(function (player) {
							if (player.uid === r.data.uid) {
								({ selected: player.selected, l_selected: player.l_selected } = r.data)

								EventBus.$emit('PlayerStatusChanged', this.rid, r.data.signed, r.data.selected)
							}
						})
					})

				}).catch((r) => {

				})
			},
			FetchRaid: function (rid) {
				this.roles.splice(0, this.roles.length)
				this.$http.get('/signup/' + rid).then((r) => {
					({ players: this.roles, total_raiders: this.total_raiders, total_selected: this.total_selected, total_signed: this.total_signed, total_unsigned: this.total_unsigned, can_select: this.can_select} = r.data)

					EventBus.$emit('FetchRaidUpdated', r.data)
				}).catch((r) => {

				})
			},
			PlayerSigned: function (data) {
				({ total_raiders: this.total_raiders, total_selected: this.total_selected, total_signed: this.total_signed, total_unsigned: this.total_unsigned} = data)

				this.roles.forEach(function (role) {
					role.players.forEach(function (player) {
						if (player.uid === data.uid) {
							player.signed = data.signed ? 1 : -1
							({ selected: player.selected, l_selected: player.l_selected } = data)
						}
					})
				})
			}
		}
	}
</script>

<template>
	<div class="signup-list">
		<ul class="raid-stats">
			<li id="raid-players-signed"><strong>{{ total_raiders }}</strong> {{ messages.L_PLAYERS}}</li>
			<li id="raid-players-signed"><strong>{{ total_signed }}</strong> {{ messages.L_PLAYERS_SIGNED}}</li>
			<li id="raid-players-signed"><strong>{{ total_unsigned }}</strong> {{ messages.L_PLAYERS_UNSIGNED}}</li>
			<li id="raid-total-selected"><strong>{{ total_selected }}</strong> {{ messages.L_PLAYERS_SELECTED}}</li>
		</ul>

		<transition-group tag="ul" name="slide" mode="out-in" class="list-signups">
			<li class="list-roles" v-for="role in roles" :key="role.name">
				<h2>{{ role.name }}</h2>

				<dl v-for="player in role.players" :key="player.uid" :class="{ 'player-is-unconfirmed': !player.signed, 'player-has-unsigned': player.signed < 0, 'player-has-signed': player.signed }">
					<dt>
						<span class="class" :class="player.css">
							<a :href="player.link">{{ player.username }}</a>
						</span>
					</dt>
					<dd v-if="player.signed > 0" class="select-or-not" :class="{ 'selected': player.selected == 1, 'reserve': player.selected == 2 }">
						<span class="right-box">
							<a v-if="can_select" class="user-select" @click.stop.prevent="SelectPlayer(player.uid)" >{{ player.l_selected }}</a>
							<span v-else>{{ player.l_selected }}</span>
						</span>
					</dd>
				</dl>
			</li>
		</transition-group>

	</div>
</template>

<style lang="scss" scoped>
	// Core variables and mixins
	@import "~bootstrap/scss/functions";
	@import "~bootstrap/scss/variables";
	@import "~bootstrap/scss/mixins/grid";
	@import "~bootstrap/scss/mixins/breakpoints";

	.signup-list {
		flex: 0 0 100%;

		@include media-breakpoint-up(md) {
			@include make-col(8);
			padding: 0 20px 0 0;
		}

		.list-signups {
			list-style: none;
			margin: 0;
			padding: 0;
			transition: all 0.75s;

			h2 {
				border-bottom: solid 1px hsla(0,0%,100%,.05);
				font-size: 1.5rem;
				margin-bottom: 5px;
				margin-top: 0;
				text-transform: capitalize;
			}

			li {
				margin-bottom: 15px;
			}

			dl {
				display: block;
				margin-bottom: 1px;
				overflow: hidden;
			}

			dt {
				display: block;
				float: left;
				width: 35%;
			}

			dd {
				color: #efefef;
				display: block;
				margin-bottom: 0;
				margin-left: 35%;
				padding: 8px;
				text-align: right;
				text-shadow: 0 1px 0 rgba(0, 0, 0, 0.7);

				.user-select:hover {
					border-bottom: 0;
					cursor: pointer;
				}
			}

			a {
				color: #efefef;
				text-shadow: none;
				text-decoration: none;
			}

			a:active {
				color: #fff;
			}

			.player-has-signed dd {
				background: linear-gradient(to right, rgba(0, 0, 0, 0) 0%,rgba(0, 0, 0, 0.33) 100%);

				&.selected {
					background: linear-gradient(to right, rgba(0, 0, 0, 0) 0%,rgba(20, 123, 21, 0.75) 100%);
				}

				&.reserve {
					background: linear-gradient(to right, rgba(0, 0, 0, 0) 0%,rgba(20, 100, 200, 0.75) 100%);
				}

				.right-box {
					position: relative;
				}
			}

			.player-has-unsigned {
				opacity: 0.2;
				position: relative;
			}
		}
	}

	.fade-enter-active {
		transition: all 0.5s;
	}

	.fade-enter {
		opacity: 0;
		transform: translateY(15px);
	}

	.fade-move {
		transition: transform 1s;
	}

	.slide-enter-active, .slide-leave-active {
		transition: all 0.5s;
	}

	.slide-leave-active {
		opacity: 0;
		transform: translateX(-20px);
	}

	.slide-enter {
		opacity: 0;
		transform: translateX(30px);
	}
</style>
