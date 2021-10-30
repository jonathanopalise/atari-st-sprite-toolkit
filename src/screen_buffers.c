#include "screen_buffers.h"

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

    uint16_t phys_base = Physbase();
    screen_buffers[visible_index].address = phys_base;
    screen_buffers[ready_index].address = phys_base - 16000;
    screen_buffers[drawing_index].address = phys_base - 32000;

    // TODO: clear buffers
}

void screen_buffers_handle_vbl()
{
	if (ready_index >= 0) {
		visible_index = ready_index;
		ready_index = -1;
		Setscreen(
            screen_buffers[visible_index].address,
            screen_buffers[drawing_index].address,
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

uint16_t *screen_buffers_get_drawing_address()
{
    return screen_buffers[visible_index].address;
}


