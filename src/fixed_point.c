#include<stdio.h>
#include<inttypes.h>

/*int16_t fixed_mul_16(int16_t x, int16_t y)
{
    return ((int32_t)x * (int32_t)y) / (1 << 8);
}

int16_t fixed_div_16(int16_t x, int16_t y)
{
    return ((int32_t)x * (1 << 8)) / y;
}*/

int16_t fixed_mul_6_10(int16_t x, int16_t y)
{
    return ((int32_t)x * (int32_t)y) / (1 << 10);
}

int16_t fixed_div_6_10(int16_t x, int16_t y)
{
    return ((int32_t)x * (1 << 10)) / y;
}


int main(int argc, char **argv)
{
    // 6.10 fixed point
    // 000000 / 0000000000
    // integer: -32 to 31
    // fractional: 0 to 1023
    // examples:
    //

    // x = 12.5 : (12 << 10) + 512 : 12800
    int x = (12 << 10) + 512;
    // y = 1.5 : (1 << 10) + 512 : 1536
    int y = (1 << 10) + 512;
    // expected: 18432 + 768 : 19200

    printf(
        "%d divided by %d = %d\n",
        x,
        y,
        fixed_mul_6_10(x,y)
    );

    // rotation:
    //
    // newX = x * cos(angle) - y * sin(angle)
    // newY = y * cos(angle) + x * sin(angle)

    // projection:
    // 
    // screenX = worldX/worldZ (+160?)
    // screenY = worldY/worldZ (+100?)
    // we need to shift left the worldX or worldY by 8 before we perform the division
    //
    // might want to shift the result left afterwards (field of view?)
    //
    // e.g.
    // worldX = 3136 (12.25)
    // worldY = 2560
    // worldZ = 4000 (15.62)
    //
    // fixed point range = -32768 to 32767 (approx -128 to 127)
    // so we need to watch out for overflows!
    //
    // screenX = 51380fp (200+160=360)
    // screenY = 41943fp

    // addition and subtraction just work
    // multiplication requires the result to be >> 8
    // using 8.8 format:
    // first number: 12.25 = 3136
    // second number: 10.00 = 2560
    //
    // division requires the first number to be shifted left first
    // using 8.8 format:
    // first number: 30.5 = 7808
    // second number: 5.5 = 1408
    // 1998848 = 1419 = 5.54
    //
    // 3136 * 2560 = 8028160
    // shift right by 8 = 31360
    // integer part: 31360 >> 8 = 122
    // fractional part: 31360 & 255 = 128 (0.5)
    //
    // result: 122.5
    //
    // sin and cos go from -1 to 1
    //
    // how does all this work with signed values?
}
