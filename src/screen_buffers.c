#include <mint/osbind.h>
#include <mint/sysbind.h>
#include "screen_buffers.h"
#include "initialise.h"

#define SCREEN_BUFFERS_COUNT 3

static int16_t visible_index;
static int16_t ready_index;
static int16_t drawing_index;

static ScreenBuffer screen_buffers[SCREEN_BUFFERS_COUNT];

void screen_buffers_init()
{
    visible_index = 0;
    ready_index = -1;
    drawing_index = 1;

    uint16_t *phys_base = Physbase();
    screen_buffers[0].address = phys_base;
    screen_buffers[1].address = phys_base - 16000;
    screen_buffers[2].address = phys_base - 32000;
}

void screen_buffers_handle_vbl()
{
	if (ready_index >= 0) {
		visible_index = ready_index;
		ready_index = -1;
        p1_initialise_sky(screen_buffers[visible_index].horizon_ypos);
		Setscreen(
            screen_buffers[drawing_index].address,
            screen_buffers[visible_index].address,
            -1
        );
    }
}

static void screen_buffers_error()
{
    while(0) {};
}

void screen_buffers_frame_complete()
{
	if (ready_index >= 0) {
		screen_buffers_error();
    } else {
		ready_index = drawing_index;
		drawing_index++;
		if (drawing_index == SCREEN_BUFFERS_COUNT) {
			drawing_index = 0;
        }
    }
}

ScreenBuffer *screen_buffers_get_drawing()
{
    return &screen_buffers[drawing_index];
}


