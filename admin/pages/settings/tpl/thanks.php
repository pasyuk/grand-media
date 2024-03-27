<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

global $gmCore;
/**
 * Credits
 */
?>
<fieldset id="gmedia_thanks" class="tab-pane">
	<style>
        #gmedia_thanks .gmedia-message {
            margin-inline: 0;
            margin-bottom: 20px;
        }

        .thanks-to {
			margin-block: 20px;
		}

        ul.gm-supporters {
            list-style: none;
            margin: 0;
            display: grid;
            gap: 10px;
            grid-template-columns: auto auto auto 1fr;
        }

        ul.gm-supporters li {
            display: grid;
	        grid-column: 1 / -1;
			column-gap: 10px;
	        grid-template-columns: subgrid;
        }

        ul.gm-supporters .__date {
            grid-column: 1;
        }

        ul.gm-supporters .__name {
            grid-column: 2;
        }

        ul.gm-supporters .__amount {
            text-align: right;
            grid-column: 3;
        }

        ul.gm-supporters .__message {
            grid-column: 2 / -1;
        }
	</style>
	<div class="error gmedia-message">
		<p><strong>Dear all,</strong><br/><br/>
			I am the developer of the <strong>Gmedia Gallery plugin</strong> and writing to ask for your help. About a year ago, I lost my home due to the Russia's war against Ukraine. Along with my home, I also lost almost everything I
			owned. Fortunately, my family and I were able to take shelter in a bomb shelter during the missile attack, but the experience was terrifying and traumatic.</p>
		<p>As a result of this tragic event, I have been struggling to make ends meet. My family and I have been living in rented apartments in different cities, which has been difficult and expensive. The situation has made it challenging
			for me to continue developing the Gmedia Gallery plugin, which I know is a valuable tool for many WordPress users.</p>
		<p>I am reaching out to you now because I need your help. If you are able, please consider donating whatever you can via PayPal (pasyuk@gmail.com) to support me and my family. Your contribution will not only help me to continue
			working on the plugin, but it will also help my family to rebuild our lives.</p>
		<p>I understand that times are tough for many people right now, and any help that you can provide would be greatly appreciated. If you are able to donate, no matter how much, and if you enjoy using the Gmedia Gallery plugin and
			would like to help me in my time of need, please consider making a donation today. Your generosity will mean the world to me and my family.</p>
		<p>Absolutely, I would be grateful for any help that anyone is able to offer, whether it be through a donation or in any other form. This could include things like spreading the word about the Gmedia Gallery plugin, providing
			feedback on the plugin's functionality, or even offering support and encouragement during this challenging time. I appreciate any support that people are willing to offer, and I am grateful for the kindness and generosity of the
			WordPress community.</p>
		<p>Thank you for taking the time to read my message, and thank you in advance for any support that you can provide.</p>
		<p>Sincerely,<br/>
			Serhii Pasyuk</p>
		<script>
          (function() {
            let timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
            let message_div = document.querySelector('#message.gmedia-message');
            let hide_btn = document.querySelector('#gmedia_credits');
            if ('Europe/Kiev' === timezone) {
              hide_btn.style.display = 'none'
            }
          })();
		</script>
	</div>

	<div class="thanks-to">
		<h4>Special thanks to:</h4>
		<?php
		$supporters = [
			'Marko D.'                   => [
				'amount'  => '€100.00',
				'date'    => '2023-04-07',
				'comment' => 'I hope you and your family are well. I hope to have helped you.',
			],
			'Tomas L.'                   => [
				'amount'  => '€10.00',
				'date'    => '2023-04-07',
				'comment' => 'Best wishes!',
			],
			'Torben B.'                  => [
				'amount'  => '€5.00',
				'date'    => '2023-04-07',
				'comment' => '',
			],
			'Duane S.'                   => [
				'amount'  => '€25.00',
				'date'    => '2023-04-07',
				'comment' => '',
			],
			'Steven F.'                  => [
				'amount'  => '€20.00',
				'date'    => '2023-04-08',
				'comment' => '',
			],
			'Mark Z.'                    => [
				'amount'  => '€20.00',
				'date'    => '2023-04-08',
				'comment' => 'Thank you for the Gmedia plugin. I just downloaded but will definitely support Ukrainians.',
			],
			'JBG Goos'                   => [
				'amount'  => '€20.00',
				'date'    => '2023-04-08',
				'comment' => '',
			],
			'Dagmar K.'                  => [
				'amount'  => '€50.00',
				'date'    => '2023-04-08',
				'comment' => 'Best wishes for you and your family. I like gmedia very much. regards Dagmar',
			],
			'Xavier B.Q.'                => [
				'amount'  => '€50.00',
				'date'    => '2023-04-08',
				'comment' => 'Good luck, if you believe in God, may he protect you...',
			],
			'Lukas W.'                   => [
				'amount'  => '€20.00',
				'date'    => '2023-04-08',
				'comment' => '',
			],
			'Affuteur Pro'               => [
				'amount'  => '€21.15',
				'date'    => '2023-04-08',
				'comment' => 'I want to help You. I\'m sorry I can\'t help more, but I too am in a difficult situation.',
			],
			'Nicole V.'                  => [
				'amount'  => '€5.00',
				'date'    => '2023-04-08',
				'comment' => '',
			],
			'Mirko G.'                   => [
				'amount'  => '€5.00',
				'date'    => '2023-04-08',
				'comment' => 'I sincerely hope that the f****** war will soon end!',
			],
			'Müller-Lüdenscheidt-Verlag' => [
				'amount'  => '€25.00',
				'date'    => '2023-04-08',
				'comment' => '',
			],
			'Clifford M.'                => [
				'amount'  => '€100.00',
				'date'    => '2023-04-09',
				'comment' => '',
			],
			'Lorenzo F.'                 => [
				'amount'  => '$10.44',
				'date'    => '2023-05-07',
				'comment' => 'Donation for Ukraine-Russia war. I hope your resilience and resistance will be an example to the whole world....',
			],
			'Brenda D.'                  => [
				'amount'  => '$50.82',
				'date'    => '2023-05-31',
				'comment' => 'donation',
			],
			'Роман К.'                   => [
				'amount'  => '$3.00',
				'date'    => '2023-07-11',
				'comment' => '',
			],
			'László G.'                  => [
				'amount'  => '$50.00',
				'date'    => '2024-03-03',
				'comment' => '',
			],

		]
		?>
		<ul class="gm-supporters">
			<?php
			foreach ( $supporters as $name => $data ) {
				?>
				<li>
					<span class="__date">[<?php echo esc_html( $data['date'] ); ?>]</span>
					<strong class="__name"><?php echo esc_html( $name ); ?></strong>
					<span class="__amount"><?php echo esc_html( $data['amount'] ); ?></span>
					<?php if ( ! empty( $data['comment'] ) ) { ?>
						<em class="__message"><?php echo esc_html( $data['comment'] ); ?></em>
					<?php } ?>
				</li>
				<?php
			}
			?>
		</ul>
	</div>
</fieldset>

