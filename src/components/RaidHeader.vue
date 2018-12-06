<script>
	import { EventBus } from './../EventBus'

	export default {
		name: 'raidHeader',
		data: function () {
			return { ...header, messages }
		},
		props: ['rid'],
		computed: {
			raidBanner: function () {
				return "/ext/numeric/raidschedule/assets/images/" + this.raid_banner
			}
		},
		methods: {
			UpdateRaid: function (data) {
				({ rid: this.rid, uid: this.uid, raid_name:this.raid_name, raid_date:this.raid_date, raid_banner:this.raid_banner, raid_posts:this.raid_posts, raid_topic:this.raid_topic, player_signed:this.player_signed, can_sign:this.can_sign } = data)
			},
			RaidSign: function () {
				this.$http.put('/signup/' + this.rid + '/sign').then((r) => {
					this.player_signed = r.data.signed

					EventBus.$emit('PlayerSigned', r.data)
				}).catch((r) => {

				})
			}
		},
	}
</script>

<template>
	<div class="raid-instance">
		<svg class="raid-instance-gradient">
			<defs>
				<linearGradient id="alphaLinearMain" x1="0%" y1="0%" x2="0%" y2="100%">
					<stop offset="0" stop-color="white" stop-opacity="100%"></stop>
					<stop offset="70%" stop-color="white" stop-opacity="100%"></stop>
					<stop offset="100%" stop-color="white" stop-opacity="0"></stop>
				</linearGradient>
				<mask id="MaskMainBg">
					<rect x="0" y="0" width="100%" height="100%" fill="url(#alphaLinearMain)"></rect>
				</mask>
				<pattern id="bgPattern" patternUnits="userSpaceOnUse" width="100%" height="100%">
					<image :href="raidBanner" x="0" y="0" width="100%" height="100%" patternUnits="objectBoundingBox" preserveAspectRatio="xMidYMid slice"></image>
				</pattern>
			</defs>
			<rect x="0" y="0" width="100%" height="100%" fill="url(#bgPattern)" mask="url(#MaskMainBg)"></rect>
		</svg>

		<div class="page-content">
			<div class="page-content-inner signup">

				<div class="raid-info">
					<div class="raid-info-title">
						<h2>{{ raid_name }} <span>{{ raid_date | formatTime('dddd DD MMMM YYYY, HH:mm') }}</span></h2>
					</div>

					<div class="sign-not-sign" :class="{'is-signed': player_signed, 'is-not-signed': !player_signed}">
						<div class="raid-info-btn">
							<button v-if="player_signed" class="btn btn-vlg btn-success" :disabled="!can_sign" @click.stop.prevent="RaidSign()"><i class="fa fa-times" aria-hidden="true"></i> {{ messages.L_UNSIGN }}</button>
							<button v-else class="btn btn-vlg btn-danger" :disabled="!can_sign" @click.stop.prevent="RaidSign()"><i class="fa fa-exclamation" aria-hidden="true"></i> {{ messages.L_SIGN }}</button>
							<span v-show="raid_topic"><a :href="raid_topic">{{ messages.L_DISCUSS_THIS }} ({{ raid_posts }})</a></span>
						</div>
					</div>
				</div>

			</div>
		</div>
	</div>
</template>

<style lang="scss">
	// Core variables and mixins
	@import "~bootstrap/scss/functions";
	@import "~bootstrap/scss/variables";
	@import "~bootstrap/scss/mixins/grid";
	@import "~bootstrap/scss/mixins/breakpoints";

	// Override main stylesheet
	main {
		padding-top: 0;
	}

	.affix {
		position: fixed;
	}

	.raid-instance-gradient {
		height: 100%;
		left: 0;
		position: absolute;
		top: 0;
		width: 100%;
	}

	.page-content-inner.signup {
		// force div to full height
		height: 100%;
	}

	.hide-text {
		font: 0/0 a;
		color: transparent;
		text-shadow: none;
		background-color: transparent;
		border: 0;
	}

	.input-block-level {
		display: block;
		width: 100%;
		min-height: 28px;
		box-sizing: border-box;
	}

	.raid-instance {
		color: #efefef;
		background: #000 url(/styles/aquila/theme/images/leather-blue.jpg) repeat;
		height: 60vh;
		margin-bottom: 0;
		position: relative;
		width: 100%;
		z-index: 1;

		&.fixed {
			position: absolute;
			top: -45px;
		}
	}

	.raid-info {
		align-self: flex-end;
		align-items: stretch;
		display: flex;
		flex-basis: 100%;
		flex-direction: row;
		flex-wrap: wrap;
		margin-bottom: 50px;
		justify-content: flex-start;

		.raid-info-title {
			flex-basis: 100%;
			margin-bottom: 15px;
			text-align: center;

			@include media-breakpoint-up(md) {
				flex: 0 0 67%;
				margin-bottom: 0;
				max-width: 67%;
				text-align: left;
			}

			h2 {
				color: #efefef;
				font-size: 48px;
				line-height: 1;
				margin: 0;
				padding: 0;

				// Date & time
				span {
					display: block;
					font-size: 16px;
					padding-top: 10px;
				}
			}
		}

		.sign-not-sign {
			flex-basis: 100%;

			@include media-breakpoint-up(md) {
				align-self: center;
				flex: 0 0 33%;
				margin-left: auto;
				max-width: 33%;
			}

			.raid-info-btn {
				text-align: center;

				@include media-breakpoint-up(md) {
					float: right;
				}

				.btn {
					border: solid 2px rgba(255, 255, 255, 0.75);
					box-shadow: 0 0 20px 2px rgba(0, 0, 0, 0.5);
					margin-bottom: 5px;
				}

				span {
					display: block;

					a {
						padding-bottom: 2px;
						color: #efefef;
						border-bottom: dotted 1px #efefef;

						&:active {
							color: #fff;
						}
					}
				}
			}
		}
	}

	/**
	*	Wrapper for the list of signups
	**/
	.raid-instance-content {
		position: relative;
	}

	.raid-stats {
		list-style: none;
		margin: 0;
		padding: 20px 0;
		text-align: center;

		li {
			display: inline;
			margin: 0 0 2px 0;
			padding: 4px 6px;
			font-size: 1.1rem;
			white-space: nowrap;
		}
	}
</style>
