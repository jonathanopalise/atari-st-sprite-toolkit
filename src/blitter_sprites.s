    public _draw_sprite

leftclipped:
    dc.w 0

rightclipped:
    dc.w 0

topclipped:
    dc.w 0

bottomclipped:
    dc.w 0

empty_pixels_on_right:
    dc.w 0

additional_word_added:
    dc.w 0

skew:
    dc.w 0

masked:
    dc.w 0

sprite_jump_table:
    dc.l 0 ; should never be used
    dc.l draw_eight_line_chunks    ; 16 wide
    dc.l draw_four_line_chunks    ; 32 wide
    dc.l draw_two_line_chunks    ; 48 wide

_draw_sprite:


    ; inputs:

    ; a0 = source data address?
    ; a1 = screen buffer address
    ; a3 = desired xpos to the pixel
    ; a4 = (nothing)
    ; a5 = (nothing)
    ; a6 = (nothing)

    ; d0 = (nothing)
    ; d1 = number of 16 pixel blocks to be drawn
    ; d2 = xpos in increments of 16 pixels (e.g. 2 = 32 pixels)
    ; d3 = (d7 - d5) + 1
    ;   (number of lines to draw) WE SHOULD BE ABLE TO CALCULATE THIS HERE
    ; d4 = (mirror of d1) WE SHOULD BE ABLE TO TRANSFER THIS HERE
    ; d5 = top of sprite ypos
    ; d6 = d2 + d1
    ;   (starting point in increments of 16 pixels + number of 16 pixel blocks to be drawn)
    ; d7 = bottom of sprite ypos

    ; what i want to pass in:
    ; xpos (signed word)
    ; ypos (signed word)
    ; source data pointer (long)
    ; source data width (word)
    ; source data height (word)
    ; screen buffer pointer (long)
    ;
    ; THEN
    ; - d1 = (source data width) >> 4
    ; - d2 = (xpos >> 4)
    ; - d3 = (source data height)
    ; - d4 = d1
    ; - d5 = (ypos)
    ; - d6 = (d1 + d2)
    ; - d7 = (d5 + d3)
    ; - a0 = (source data pointer)
    ; - a1 = (screen buffer pointer)
    ; - a3 = (xpos)

    ; xpos = word at sp + 6
    ; ypos = word at sp + 8
    ; source_data = long at sp + 12
    ; source_data_width = word at sp + 18
    ; source_data_height = word at sp + 22
    ; screen_buffer = long at sp + 24

    move.l sp,a0
    movem.l d2-d7/a2-a6,-(sp)

    moveq.l #0,d1
    move.w 18(a0),d1 ; source_data_width
    asr.w #4,d1
    move.l d1,d4

    moveq.l #0,d2
    move.w 6(a0),d2 ; xpos
    move.l d2,a3
    asr.w #4,d2

    moveq.l #0,d3
    move.w 22(a0),d3 ; source_data_height

    moveq.l #0,d5
    move.w 10(a0),d5 ; ypos

    move.l d1,d6
    add.l d2,d6 

    move.l d5,d7
    add.l d3,d7
    tst.w d3
    beq alldone

    move.l 24(a0),a1 ; screen buffer
    move.w 30(a0),empty_pixels_on_right
    move.w 34(a0),masked

    move.l 12(a0),a0 ; source data pointer

    moveq     #0,d0
    move.w    d0,leftclipped
    move.w    d0,rightclipped
    move.w    d0,topclipped
    move.w    d0,bottomclipped
    move.w    d0,additional_word_added

    move.l a3,d0                       ; get desired xpos of scenery object
    and.l #$f,d0                       ; convert to skew value for blitter
    move.w d0,skew
    cmp.w empty_pixels_on_right,d0
    ble.s zeroskew

    ;beq.s zeroskew

    move.w d2,d0                       ; get starting position in blocks of 16 pixels
    add.w d4,d0                        ; add number of 16 pixel blocks to be drawn

    cmp.w #$14,d0                      ; will part of sprite be off right side if we add 16 pixels?
    bpl.s setrightclipped              ; if yes, don't add 16 pixels to the right side

    addq.w #1,d4                        ; add another 16 pixel block to account for skew
    move.w #1,additional_word_added
    bra.s zeroskew

setrightclipped:

    move.w    #$ffff,rightclipped

zeroskew:

    tst.w     d2                       ; set flags for d2
    bpl.s     label_7a32c              ; jump when no left clipping required
    tst.w     d6                       ; bytes to skip after each line
    bmi       alldone                  ; if negative, nothing to draw
    move.w    d2,d0
    moveq     #0,d2                    ; clip scenery against left (left endmask should be 0xffff)
                                       ; at this point, left endmask needs be to 0xffff
    move.w    #$ffff,leftclipped
    add.w     d0,d4
    add.w     d0,d0
    suba.w    d0,a0
    add.w     d0,d0
    add.w     d0,d0
    suba.w    d0,a0

label_7a32c:
    tst.w     d5                       ; do we need to clip the top of the sprite?
    bpl.s     label_7a348              ; if we take the jump, no need to clip the top
                                       ; at this point, top is being clipped
    tst.w     d7                       ; is the bottom of the sprite off screen too?
    bmi     alldone                  ; if so, nothing to draw

    move.w    d5,d0
    moveq     #0,d5
    add.w     d0,d3
    tst.w     d3
    beq alldone

    add.w     d0,d0
    muls.w    d1,d0
    suba.w    d0,a0
    add.w     d0,d0
    add.w     d0,d0
    suba.w    d0,a0

label_7a348:
    cmp.w     #$14,d6
    bmi.s     label_7a35e              ; something to do with clipping against right side of screen
    cmp.w     #$14,d2                  ; does sprite need clipping on right edge?
    bpl       alldone            ; something to do with clipping - if sprite is entirely off screen?
    move.w    d6,d0
    subi.w    #$14,d0
    sub.w     d0,d4                    ; this is chopping off the sprite on the right edge
    move.w    #$ffff,rightclipped

    ; sprite has been clipped on right edge
    ; so endmask3 needs to be $ffff

label_7a35e:
    cmp.w     #199,d7   ; clip against bottom of screen
    bls.s     label_7a374
    sub.w     #199,d7
    addq.w    #1,d7
    sub.w     d7,d3           ; cut the bottom off the sprite
    bls     alldone

label_7a374:
    move.w    d4,d6
    add.w     d6,d6
    subi.w    #$28,d6
    neg.w     d6
    move.w    d1,d7            ; d7 = d1
    sub.w     d4,d7            ; d7 = d1 - d4 (result: blocks of 16 pixels to skip after each line)

    move.w    d7,d0            ; ...
    add.w     d0,d0            ; ...
    add.w     d0,d0            ; ...
    add.w     d0,d7            ; ...
    add.w     d7,d7            ; d7 = d7 * 10 (final value for number of source bytes to skip after each line)

    asl.w     #2,d6            ; d6 = d6 * 4 (final value of destination bytes to skip after each line)

    add.w     d2,d2            ; d2 = d2 * 2
    move.w    d5,d0            ; begin expression...
    add.w     d0,d0            ; ...
    add.w     d0,d0            ; ...
    add.w     d0,d5            ; ...
    asl.w     #3,d5            ; ... d5 = d5 * 40
    add.w     d5,d2            ; begin expression...
    add.w     d2,d2            ; ...
    add.w     d2,d2            ; ...
    ;adda.w    d2,a2            ; ... d2 = (d2 * 8 [see 7a38c]) + d5 (d5 must the start of a line within logbase, so a multiple of 160)
                                                                              ; we set d5 to 0 and everything renders at the top line of the screen
    ;adda.l    a1,a2        ; add buffer location into a2?
    ;movea.l   a2,a1            ; transfer destination address into a1
    add.w     d2,a1
    tst.w     d4
    beq       alldone
 
    ; end of modified lotus code and start of new blitter code
    moveq.l #10,d5

    ; draw a roadside object
    ; a0 is source address
    ; a1 is destination address
    ; d3 is the lines to be drawn
    ; d4 is number of 16 pixel blocks to be drawn (= 8 words)
    ; - so if d4 = 1, we want to draw 16 pixels = 4 words = 8 bytes
    ; d6 is destination bytes to skip after each line
    ; d7 is source bytes to skip after each line

    lea $ffff8a38.w,a2
    lea $ffff8a24.w,a4
    lea $ffff8a32.w,a5
    lea $ffff8a3c.w,a6

    addq.l #8,d6               ; convert to value suitable for blitter
    add.w d5,d7               ; convert to value suitable for blitter | TODO: #10 for 4bpp and #8 for 3bpp

    move.w d5,($ffff8a20).w   ; source x increment | TODO: #10 for 4bpp and #8 for 3bpp
    move.w #8,($ffff8a2e).w    ; dest x increment
    move.w #$201,($ffff8a3a).w ; hop/op: read from source, source & destination

    move.l a3,d0               ; get desired xpos of scenery object
    and.w #$f,d0               ; convert to skew value for blitter

    move.w d0,d1
    ;beq.s nonfsr               ; if skew is zero, we can't use nfsr

    ;cmp.w #1,d4
    ;beq.s nonfsr

    ;tst.w rightclipped
    ;bne.s nonfsr

    ;add.w d5,d7               ; TODO: #10 for 4bpp, #8 for 3bpp
    ;or.b #$40,d1

;nonfsr:

    tst.w leftclipped
    beq.s nofxsr

    sub.w d5,d7     ; TODO: #10 for 4bpp, #8 for 3bpp
    sub.l d5,a0

    or.b #$80,d1

    cmp.w #1,d4
    bne.s nofxsr 

    ; when words to draw = 4 and leftclipped != 0, we need to set endmask1 from rightendmasks
    ; In the case of a one word line ENDMASK 1 is used (http://www.atari-wiki.com/index.php/Blitter_manual)
    ; this is a special case and could do with tidying up

    move.w d7,($ffff8a22).w             ; source y increment
    move.w d6,($ffff8a30).w             ; dest y increment
    move.w d4,($ffff8a36).w             ; xcount = number of 16 pixel blocks (one pass per bitplane)
    move.b d1,($ffff8a3d).w

    lea.l rightendmasks(pc),a3
    add.l d0,d0                         ; byte offset in mask lookup table
    move.w (a3,d0.w),d1
    move.w d1,($ffff8a28).w             ; endmask1
    bra.s blitterstart

nofxsr:

    move.w d7,($ffff8a22).w             ; source y increment
    move.w d6,($ffff8a30).w             ; dest y increment
    move.w d4,($ffff8a36).w             ; xcount = number of 16 pixel blocks (once pass per bitplane)
    move.b d1,($ffff8a3d).w

    add.l d0,d0                         ; byte offset in mask lookup table
    move.w #-1,($ffff8a2a).w            ; endmask2

    move.w leftclipped(pc),d1
    bne.s nocalcendmask1                ; branch if zero flag not set

    lea.l leftendmasks(pc),a3
    move.w (a3,d0.w),d1                 ; fetch value of endmask1

nocalcendmask1:
    move.w d1,($ffff8a28).w             ; endmask1

    move.w rightclipped(pc),d1
    bne.s nocalcendmask3                ; branch if zero flag not set

    tst.w additional_word_added
    bne.s normalendmask3

    ; how many pixels do we need to draw?
    ; (16 - empty_right_pixels) + skew

    ;lsr.w #1,d0

    move.w skew,d0
    move.w #15,d1
    sub.w empty_pixels_on_right,d1
    add.w d1,d0
    add.w d0,d0

    ;move.w (a3,d0.w),d1
    ;move.w d1,($ffff8a2c).w            ; endmask3

    ;move.w #$ffff,($ffff8a2c).w            ; endmask3

    bra.s applyendmask

normalendmask3:
    move.w skew,d0
    sub.w empty_pixels_on_right,d0
    add.w d0,d0

applyendmask:
    lea.l rightendmasks(pc),a3
    move.w (a3,d0.w),d1

nocalcendmask3:
    move.w d1,($ffff8a2c).w            ; endmask3

    ; we are now free to use d0, d6 and d4 for our own purposes
    ; looks like d0, d1 and d2 are also available to us

blitterstart:

    cmp.w #3,d4
    bgt draw_one_line_chunks

    lea sprite_jump_table(pc),a3
    move.w d4,d2
    add.w d2,d2
    add.w d2,d2
    move.l (a3,d2.w),a3
    jmp (a3)

;draw_all:
;    move.w #798,2+drawsceneryplane_jsr    ; jump address in unrolled blitter calling table
;    move.w d3,finalblit+2                 ; ycount
;    bra.s draw_now

draw_eight_line_chunks:
    lea eight_line_chunks_lookup(pc),a3
    move.w d3,d2
    and.w #7,d2
    move.b (a3,d2.w),d2
    move.w d2,finalblit+2

    move.w #798,d1
    subq.w #1,d3
    and.w #$f8,d3
    lsr.w #1,d3
    sub.w d3,d1
    move.w d1,2+drawsceneryplane_jsr    ; jump address in unrolled blitter calling table
    moveq.l #8,d1                       ; ycount
    bra.s draw_now

eight_line_chunks_lookup:
    dc.b 8
    dc.b 1
    dc.b 2
    dc.b 3
    dc.b 4
    dc.b 5
    dc.b 6
    dc.b 7

draw_four_line_chunks:
    lea four_line_chunks_lookup(pc),a3
    move.w d3,d2
    and.w #3,d2
    move.b (a3,d2.w),d2
    move.w d2,finalblit+2

    move.w #798,d1
    subq.w #1,d3
    and.w #$fc,d3
    sub.w d3,d1
    move.w d1,2+drawsceneryplane_jsr    ; jump address in unrolled blitter calling table
    moveq.l #4,d1                       ; ycount
    bra.s draw_now

four_line_chunks_lookup:
    dc.b 4
    dc.b 1
    dc.b 2
    dc.b 3

draw_two_line_chunks:
    lea two_line_chunks_lookup(pc),a3
    move.w d3,d2
    and.w #1,d2
    move.b (a3,d2.w),d2
    move.w d2,finalblit+2

    move.w #798,d1
    subq.w #1,d3
    and.w #$fe,d3
    add.w d3,d3
    sub.w d3,d1
    move.w d1,2+drawsceneryplane_jsr    ; jump address in unrolled blitter calling table
    moveq.l #2,d1                       ; ycount
    bra.s draw_now

two_line_chunks_lookup:
    dc.b 2
    dc.b 1

draw_one_line_chunks:
    move.w #802,d1                      ; size of unrolled blitter calling table plus 2
    lsl.w #2,d3                         ; one entry in the table is 4 bytes
    sub.w d3,d1                         ; generate value to be placed within modified bra instruction
    move.w d1,2+drawsceneryplane_jsr    ; jump address in unrolled blitter calling table
    moveq.l #1,d1                       ; ycount
    move.w d1,finalblit+2

draw_now:
    move.b #$c0,d6                      ; blitter start instruction

    tst.w masked
    beq.s colour_unmasked

    rept 3
    bsr.s drawsceneryplane
    addq.l #2,a1                        ; move to next bitplane
    endr
    bsr.s drawsceneryplane

    subq.l #6,a1                        ; move destination back to initial bitplane

    move.w #$0207,($ffff8a3a).w         ; hop/op: read from source, source | destination
    bra.s colour

colour_unmasked:
    move.w #$0203,($ffff8a3a).w         ; hop/op: read from source, solid colour

colour:
    addq.l #2,a0                        ; move source to next bitplane
    bsr.s drawsceneryplane
    addq.l #2,a1                        ; move destination to next bitplane
    addq.l #2,a0                        ; move source to next bitplane
    bsr.s drawsceneryplane
    addq.l #2,a1                        ; move destination to next bitplane
    addq.l #2,a0                        ; move source to next bitplane
    bsr.s drawsceneryplane

    ;cmp.w #10,d5
    ;bne.s alldone

    ; stop here if 3bpp
    addq.l #2,a1                        ; move destination to next bitplane
    addq.l #2,a0                        ; move source to next bitplane
    bsr.s drawsceneryplane

alldone:
    ;nop
    ;bra.s alldone

    movem.l (sp)+,d2-d7/a2-a6

    rts

drawsceneryplane:
    move.l a0,(a4)             ; set source address
    move.l a1,(a5)             ; set destination

drawsceneryplane_jsr:
    bra drawsceneryplane_aft
    rept 199
    move.w d1,(a2)             ; ycount
    move.b d6,(a6)
    endr
finalblit:
    move.w #1,(a2)
    move.b d6,(a6)
drawsceneryplane_aft:
    rts

leftendmasks:

    dc.w %1111111111111111
    dc.w %0111111111111111
    dc.w %0011111111111111
    dc.w %0001111111111111
    dc.w %0000111111111111
    dc.w %0000011111111111
    dc.w %0000001111111111
    dc.w %0000000111111111
    dc.w %0000000011111111
    dc.w %0000000001111111
    dc.w %0000000000111111
    dc.w %0000000000011111
    dc.w %0000000000001111
    dc.w %0000000000000111
    dc.w %0000000000000011
    dc.w %0000000000000001

rightendmasks:

    dc.w %1111111111111111
    dc.w %1000000000000000
    dc.w %1100000000000000
    dc.w %1110000000000000
    dc.w %1111000000000000
    dc.w %1111100000000000
    dc.w %1111110000000000
    dc.w %1111111000000000
    dc.w %1111111100000000
    dc.w %1111111110000000
    dc.w %1111111111000000
    dc.w %1111111111100000
    dc.w %1111111111110000
    dc.w %1111111111111000
    dc.w %1111111111111100
    dc.w %1111111111111110


