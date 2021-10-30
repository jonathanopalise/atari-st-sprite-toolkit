    public _initialise
    public _joy_data

_initialise:

		move.w	#$2700,sr			;Stop all interrupts

        move.l ($118),a0
        move.l a0,(oldikbd)

		move.l	#vbl,$70.w			;Install our own VBL
		move.l	#dummy,$68.w			;Install our own HBL (dummy)
		move.l	#dummy,$134.w			;Install our own Timer A (dummy)
        move.l  #timer_1,$120.w            ;Install our own Timer B
		;move.l	#dummy,$120.w			;Install our own Timer B
		move.l	#dummy,$114.w			;Install our own Timer C (dummy)
		move.l	#dummy,$110.w			;Install our own Timer D (dummy)
		move.l	#newikbd,$118.w			;Install our own ACIA (dummy)
		clr.b	$fffffa07.w			;Interrupt enable A (Timer-A & B)
		clr.b	$fffffa13.w			;Interrupt mask A (Timer-A & B)
		move.b	#$12,$fffffc02.w		;Kill mouse
     
       move.w #34,-(a7)
       trap   #14
       addq.l #2,a7            ;return IKBD vector table
       move.l d0,a0            ;a0 points to IKBD vectors
       move.l #read_joy,24(a0) ;input my joystick vector
	   move.l #joy_on,-(a7)    ;pointer to IKBD instructions
       move.w #1,-(a7)         ;2 instructions
       move.w #25,-(a7)        ;send instruction to IKBD
       trap   #14
       addq.l #8,a7

		move.w	#$2300,sr			;Interrupts back on
		rts


read_joy:
       move.b 2(a0),_joy_data ;store joy 1 data
       rts                       ;note, rts, not rte

dummy:
	rte

timer_1:
        move.w  #$2700,sr         ;Stop all interrupts
        move.l  #timer_2,$120.w            ;Install our own Timer B
        move.b  #8,$fffffa1b.w         ;Timer B control (event mode (HBL))
        move.w #$1c7,$ffff825e.w ; colour 10 = 474 hud laptime
        move.w  #$2300,sr         ;Interrupts back on
     rte

timer_2:
        move.w  #$2700,sr         ;Stop all interrupts
        move.l  #timer_3,$120.w            ;Install our own Timer B
        move.b  #8,$fffffa1b.w         ;Timer B control (event mode (HBL))
        move.w #$157,$ffff825e.w ; colour 10 = 474 hud laptime
        move.w  #$2300,sr         ;Interrupts back on
     rte

timer_3:
        move.w  #$2700,sr         ;Stop all interrupts
        move.l  #timer_b,$120.w            ;Install our own Timer B
        move.b  #8,$fffffa1b.w         ;Timer B control (event mode (HBL))
        move.w #$0235,$ffff8248.w ; colour 4 - new fuji colour
        move.w #$1d7,$ffff825e.w ; colour 10 = 474 hud laptime
        move.w  #$2300,sr         ;Interrupts back on
     rte

timer_4:
        move.w  #$2700,sr         ;Stop all interrupts
        move.l  #timer_b,$120.w            ;Install our own Timer B
        move.b  #8,$fffffa1b.w         ;Timer B control (event mode (HBL))
        move.w #$666,$ffff825e.w ; colour 10 = 474 hud laptime
        move.w  #$2300,sr         ;Interrupts back on
     rte

timer_5:
        move.w  #$2700,sr         ;Stop all interrupts
        move.l  #timer_6,$120.w            ;Install our own Timer B
        clr.b   $fffffa1b.w            ;Timer B control (stop)
        move.b  #10,$fffffa21.w            ;Timer B data (number of scanlines to next interrupt)
        move.b  #8,$fffffa1b.w         ;Timer B control (event mode (HBL))
        move.w  #$2300,sr         ;Interrupts back on
     rte

timer_6:
        move.w  #$2700,sr         ;Stop all interrupts
        move.l  #timer_7,$120.w            ;Install our own Timer B
        clr.b   $fffffa1b.w            ;Timer B control (stop)
        move.b  #10,$fffffa21.w            ;Timer B data (number of sc
        move.b  #8,$fffffa1b.w         ;Timer B control (event mode (H
        move.w  #$2300,sr         ;Interrupts back on
     rte

timer_7:
        move.w  #$2700,sr         ;Stop all interrupts
        move.l  #timer_b,$120.w            ;Install our own Timer B
        clr.b   $fffffa1b.w            ;Timer B control (stop)
        move.b  #10,$fffffa21.w            ;Timer B data (number of sc
        move.b  #8,$fffffa1b.w         ;Timer B control (event mode (H
        move.w  #$2300,sr         ;Interrupts back on
     rte

timer_b:
        move.l #$02510125,$ffff8242.w
        move.l #$02220137,$ffff8246.w
        move.w #$555,$ffff825e.w ; colour 13 = 555 light grey
        move.l #$00070400,$ffff825c.w
        ;move.w #$750,$ffff8254.w ; colour 10 = 750 light orange
        clr.b   $fffffa1b.w            ;Timer B control (stop)
    rte



vbl:
    movem.l d0-d7/a0-a6,-(sp)
    jsr _screen_buffers_handle_vbl

        move.w  #$2700,sr         ;Stop all interrupts
        move.l  #timer_1,$120.w            ;Install our own Timer B
        clr.b   $fffffa1b.w            ;Timer B control (stop)
        bset    #0,$fffffa07.w         ;Interrupt enable A (Timer B)
        bset    #0,$fffffa13.w         ;Interrupt mask A (Timer B)
        ;move.b  #22,$fffffa21.w            ;Timer B data (number of s
        bclr    #3,$fffffa17.w         ;Automatic end of interrupt
        ;move.b  #8,$fffffa1b.w         ;Timer B control (event mode (
        move.w  #$2300,sr         ;Interrupts back on

    jsr p1_initialise_sky
    jsr p1_raster_routine


    movem.l (sp)+,d0-d7/a0-a6
    rte

newikbd:
    move d0,-(sp)
    move sr,d0
    and #$f8ff,d0
    or #$500,d0
    move d0,sr
    move (sp)+,d0
    dc.w $4ef9

oldikbd:
    dc.l 0

joy_on: dc.b   $14,$12
_joy_data: dc.b 1
pad: dc.b 1

; sky gradient

solid_sky_rgb_value:
    dc.w $a0a
gradient_rgb_values:
    dc.w $a9a
    dc.w $b9a
    dc.w $baa
    dc.w $caa
    dc.w $cba
    dc.w $dba
    dc.w $dca
    dc.w $ec9
    dc.w $ed9
    dc.w $fd9
    dc.w $fe9
    dc.w $fe9
    dc.w $ff9
    dc.w $ff9
    dc.w $ff9
    dc.w $555
    dc.w $555
    dc.w $555
    dc.w $555

bars_lookup:
    dc.b 4
    dc.b 1
    dc.b 2
    dc.b 3
    dc.b 4
    dc.b 1
    dc.b 2
    dc.b 3

raster_count:
    dc.b 4
    dc.b 0 ; to align

current_gradient_address:
    dc.l 0

new_raster_routine:
    subq.b #1,raster_count
    beq.s final_bar

    move.l    a0,usp
    addq.l    #2,current_gradient_address
    move.l    current_gradient_address(pc),a0
    move.w    (a0),$ffff825e.w
    move.l    usp,a0
    clr.b     $fffffa1b.w
    move.b    #4,$fffffa21.w
    move.b    #8,$fffffa1b.w
    bclr      #0,$fffffa0f.w
    rte

substitute_70684:
    move.w    #$40,$ffff825e.w
    rte

final_bar:
    clr.b     $fffffa1b.w
final_bar_line_count_instruction:
    move.b    #$68,$fffffa21.w
    move.b    #8,$fffffa1b.w
final_bar_vector_instruction:
    move.l    #substitute_70684,$0120.w
    bclr      #0,$fffffa0f.w
    rte

p1_sky_initialised:
    dc.w $0
p1_sky_line_count:
    dc.b $0
    dc.b $0 ; for padding
p1_final_bar_vector_instruction_plus_2:
    dc.l $0
p1_raster_count:
    dc.b $0
    dc.b $0 ; for padding
p1_final_bar_line_count_instruction_plus_3:
    dc.b $0
    dc.b $0 ; for padding
p1_current_gradient_address:
    dc.l $0
p1_gradient_start_colour:
    dc.w $0
p1_new_routine_after_lines:
    dc.b $0
    dc.b $0 ; for padding
p1_new_routine_after_vector:
    dc.l $0

p1_initialise_sky:
    ; TODO: what to do here?
    ;move.w d0,$70668 ; number of lines between top of screen and first interrupt trigger!
    move.w #30,d0 ; so this is the height of the horizon in pixels / benchmark value is 64

    move.b d0,d1
    move.b d1,p1_sky_line_count
    bsr.s p1_initialise_sky_variables
    move.w #1,p1_sky_initialised
    rts

p1_initialise_sky_variables:
    movem.l d0-d4/a0,-(sp)

    move.l #substitute_70684,p1_final_bar_vector_instruction_plus_2
    ; TODO: what to do here?

    neg.w d0
    add.w #60,d0

    ;move.w #-24,d0 ; gradient_y_at_screen_top / benchmark value is 0
    ;asr.w #1,d0
    ;add.w #21,d0

    move.w d0,d1
    move.w d0,d3 ; copy gradient_y_at_screen_top
    neg.w d1 ; $solidLinesRequired = -$gradientYAtScreenTop;

    ; d1 is now solidLinesRequired

    tst.w d1 ; test solidLinesRequired
    bgt.s p1_solid_lines_required_greater_than_zero ; if solid lines required less than or equal to zero, branch

p1_solid_lines_required_zero_or_less:

    ; $initialGradientRgb = $gradientLookup[$gradientYAtScreenTop >> 2];
    lsr.w #2,d0
    add.w d0,d0
    ext.l d0
    add.l #gradient_rgb_values,d0 ; d0 is now start gradient address

    ; is lines remaining > 3?
    moveq.l #0,d2
    move.b p1_sky_line_count(pc),d2 ; lines remaining
    cmp.b #4,d2
    bls.s p1_lines_remaining_less_than_or_equal_to_4
    ; we want to branch if lines remaining <=4

p1_lines_remaining_greater_than_4:

    lea bars_lookup(pc),a0
    and.w #3,d1 ; solid_lines_required &=3
    move.b (a0,d1.w),d1 ; new_routine_after: d1 = bars_lookup[$solidLinesRequired & 3];

    and.w #3,d3 ; gradient_y_at_screen_top &= 3
    move.w d2,d4 ; copy lines remaining for later
    and.w #3,d2 ; lines_remaining &= 3
    add.w d3,d2
    move.b (a0,d2.w),d2 ; final_bar_size: d2 = bars_lookup[($gradientYAtScreenTop & 3)+($linesRemaining & 3)];

    sub.w d2,d4
    sub.w d1,d4
    lsr.w #2,d4
    addq.w #1,d4

    move.b d4,p1_raster_count
    move.b d2,p1_final_bar_line_count_instruction_plus_3
    move.l d0,p1_current_gradient_address
    ; TODO: don't forget these lines below in the new handler
    move.l d0,a0
    move.w    (a0),p1_gradient_start_colour

    move.b    d1,p1_new_routine_after_lines ; new routine after
    move.l    #new_raster_routine,p1_new_routine_after_vector

    move.b  #8,$fffffa1b.w         ;Timer B control (event mode (HBL))

    bra.s p1_endvbl

p1_lines_remaining_less_than_or_equal_to_4:
    ; special case, not yet worked out, so just use default code

    bra.s p1_legacy

p1_solid_lines_required_greater_than_zero:

    move.w solid_sky_rgb_value(pc),p1_gradient_start_colour

    ; d1 is solidlinesrequired
    moveq.l #0,d2
    move.b p1_sky_line_count(pc),d2 ; put lines remaining into d2

    sub.w d1,d2 ; d2 = lines remaining - solid lines required
    ble.s p1_legacy ; no gradient visible

    move.w d2,d3 ; copy linesRemainingMinusSolidLinesRequired into d3

    ; now calculate raster count
    addq.w #3,d2
    lsr.w #2,d2

    ; now calculate final bar size
    and.w #3,d3
    lea bars_lookup(pc),a0
    move.b (a0,d3.w),d3 ; new_routine_after: d1 = bars_lookup[$solidLinesRequired & 3];
 
    move.b d2,p1_raster_count
    move.b d3,p1_final_bar_line_count_instruction_plus_3

    lea gradient_rgb_values(pc),a0
    move.l a0,p1_current_gradient_address

    move.b    d1,p1_new_routine_after_lines ; new routine after
    move.l    #new_raster_routine,p1_new_routine_after_vector

    bra.s p1_endvbl
    ; $solidLinesRequired > 0
    ; special case, not yet worked out, so just use default code

p1_legacy:
    move.b p1_sky_line_count(pc),p1_new_routine_after_lines ; number of lines
    move.l #substitute_70684,p1_new_routine_after_vector
p1_endvbl:
    movem.l (sp)+,d0-d4/a0
    rts

;----- END OF NEW CODE

; do i need to jump here from somewhere else?
p1_raster_routine:
    ; code to keep START

    move.b #0,$fffffa1b.w ; turn off timer b

    tst.w p1_sky_initialised
    beq.s p1_raster_not_initialised

    ;cmp.w #$684,$70676
    ;bne.s p1_raster_not_initialised

    move.l p1_final_bar_vector_instruction_plus_2(pc),final_bar_vector_instruction+2
    move.b p1_final_bar_line_count_instruction_plus_3(pc),final_bar_line_count_instruction+3
    move.l p1_current_gradient_address(pc),current_gradient_address
    move.b p1_raster_count(pc),raster_count
    move.w p1_gradient_start_colour(pc),$ffff825e.w
    move.b p1_new_routine_after_lines(pc),$fffffa21.w ; number of lines
    move.b #8,$fffffa1b.w ; turn on timer b
    move.l p1_new_routine_after_vector(pc),$0120.w
    ;bclr #0,$fffffa0f.w

    rts
    ; code to keep END

    ;move.b #0,$fffffa1b.w
    ;jmp $70666

p1_raster_not_initialised:
    rts



